<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    if (!$nome) {
        $erro = 'Informe o nome do assunto.';
    } else {
        $slug = slugify($nome);
        $ok   = gh_save_file(
            "assuntos/$slug/uploads/.gitkeep",
            "# $nome\n",
            "feat: cria assunto $slug"
        );
        if ($ok) {
            redirect("/assunto.php?slug=$slug&novo=1");
        } else {
            $erro = 'Erro ao criar assunto no GitHub. Verifique o token e repo em config.php.';
        }
    }
}

layout_head('Novo Assunto');
?>
<div class="container" style="max-width:520px">
  <a href="/" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← Voltar</a>

  <p class="page-title">📂 Novo Assunto</p>
  <p class="page-sub">Crie um assunto para centralizar os arquivos.</p>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post">
      <div class="form-group">
        <label for="nome">Nome do assunto</label>
        <input type="text" id="nome" name="nome" placeholder="Ex: Open Finance" autofocus required>
      </div>
      <button type="submit" class="btn btn-primary">Criar Assunto</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
