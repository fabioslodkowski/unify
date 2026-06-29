<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

global $USUARIOS;

$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
if (!$slug) redirect('/');

$nome = ucwords(str_replace('-', ' ', $slug));
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $novo_usr = trim($_POST['novo_usuario'] ?? '');

    // se escolheu "outro", usa o novo
    if ($usuario === '__novo__' && $novo_usr) {
        $usuario = slugify($novo_usr);
    }

    if (!$usuario) {
        $erro = 'Selecione ou informe um usuário.';
    } elseif (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Selecione um arquivo .md válido.';
    } else {
        $file     = $_FILES['arquivo'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'md') {
            $erro = 'Apenas arquivos .md são aceitos.';
        } else {
            $conteudo  = file_get_contents($file['tmp_name']);
            $nome_arq  = slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '.md';
            $path_gh   = "assuntos/$slug/uploads/$usuario/$nome_arq";

            $ok = gh_save_file($path_gh, $conteudo, "upload: $usuario/$nome_arq em $slug");
            if ($ok) {
                redirect("/assunto.php?slug=$slug&uploaded=" . urlencode($nome_arq));
            } else {
                $erro = 'Erro ao salvar no GitHub. Verifique token e repo em config.php.';
            }
        }
    }
}

layout_head('Upload — ' . $nome);
?>
<div class="container" style="max-width:520px">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome) ?></a>

  <p class="page-title">📎 Enviar arquivo</p>
  <p class="page-sub">Assunto: <strong><?= htmlspecialchars($nome) ?></strong></p>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

      <div class="form-group">
        <label for="usuario">Usuário</label>
        <select id="usuario" name="usuario" onchange="toggleNovoUsuario(this)">
          <?php foreach ($USUARIOS as $u_slug => $u_nome): ?>
            <option value="<?= htmlspecialchars($u_slug) ?>"><?= htmlspecialchars($u_nome) ?></option>
          <?php endforeach; ?>
          <option value="__novo__">+ Outro usuário…</option>
        </select>
      </div>

      <div class="form-group" id="novo-usuario-group" style="display:none">
        <label for="novo_usuario">Nome do novo usuário</label>
        <input type="text" id="novo_usuario" name="novo_usuario" placeholder="Ex: carlos">
      </div>

      <div class="form-group">
        <label for="arquivo">Arquivo .md</label>
        <input type="file" id="arquivo" name="arquivo" accept=".md">
        <p style="font-size:.8rem;color:#6b7280;margin-top:.3rem;">Apenas arquivos <code>.md</code></p>
      </div>

      <button type="submit" class="btn btn-primary">Enviar arquivo</button>
    </form>
  </div>
</div>

<script>
function toggleNovoUsuario(sel) {
  document.getElementById('novo-usuario-group').style.display =
    sel.value === '__novo__' ? 'block' : 'none';
}
</script>
<?php layout_foot(); ?>
