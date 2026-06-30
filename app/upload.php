<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

global $USUARIOS;

$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
if (!$slug) redirect('/');

$nome    = ucwords(str_replace('-', ' ', $slug));
$erros    = [];
$enviados = [];
$ignorados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $novo_usr = trim($_POST['novo_usuario'] ?? '');

    if ($usuario === '__novo__' && $novo_usr) {
        $usuario = slugify($novo_usr);
    }

    if (!$usuario) {
        $erros[] = 'Selecione ou informe um usuário.';
    } elseif (empty($_FILES['arquivos']['name'][0])) {
        $erros[] = 'Selecione pelo menos um arquivo .md.';
    } else {
        $files = $_FILES['arquivos'];
        $total = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $erros[] = "Erro ao receber '{$files['name'][$i]}' (código {$files['error'][$i]}).";
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if ($ext !== 'md') {
                $erros[] = "'{$files['name'][$i]}' ignorado — apenas arquivos .md são aceitos.";
                continue;
            }

            $conteudo = file_get_contents($files['tmp_name'][$i]);
            $nome_arq = slugify(pathinfo($files['name'][$i], PATHINFO_FILENAME)) . '.md';
            $path_gh  = "assuntos/$slug/uploads/$usuario/$nome_arq";

            // ignora se já existe
            if (gh_get_file($path_gh) !== null) {
                $ignorados[] = $nome_arq;
                continue;
            }

            $ok = gh_save_file($path_gh, $conteudo, "upload: $usuario/$nome_arq em $slug");
            if ($ok) {
                $enviados[] = $nome_arq;
            } else {
                $erros[] = "Erro ao salvar '{$nome_arq}' no GitHub.";
            }
        }

        if (!empty($enviados) && empty($erros) && empty($ignorados)) {
            redirect("/assunto.php?slug=$slug&uploaded=" . urlencode(count($enviados) . ' arquivo(s)'));
        }
    }
}

layout_head('Upload — ' . $nome);
?>
<div class="container" style="max-width:520px">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome) ?></a>

  <p class="page-title">📎 Enviar arquivos</p>
  <p class="page-sub">Assunto: <strong><?= htmlspecialchars($nome) ?></strong></p>

  <?php foreach ($erros as $e): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <?php if (!empty($enviados)): ?>
    <div class="alert alert-success">
      ✅ <?= count($enviados) ?> arquivo(s) enviado(s): <?= implode(', ', array_map('htmlspecialchars', $enviados)) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($ignorados)): ?>
    <div class="alert alert-info">
      ⏭ <?= count($ignorados) ?> já existia(m) e foi(ram) ignorado(s): <?= implode(', ', array_map('htmlspecialchars', $ignorados)) ?>
    </div>
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
        <label for="arquivos">Arquivos .md <span style="color:var(--gray-600);font-weight:400">(pode selecionar vários)</span></label>
        <input type="file" id="arquivos" name="arquivos[]" accept=".md" multiple>
        <p style="font-size:.8rem;color:#6b7280;margin-top:.3rem;">Segure <kbd>Ctrl</kbd> para selecionar múltiplos arquivos</p>
      </div>

      <button type="submit" class="btn btn-primary">Enviar arquivos</button>
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
