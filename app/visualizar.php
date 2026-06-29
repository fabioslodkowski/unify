<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/layout.php';

$path    = trim($_GET['path'] ?? '');
$assunto = trim($_GET['assunto'] ?? '');

if (!$path) redirect('/');

$md     = gh_get_content($path);
$titulo = basename($path);

if (!$md) {
    layout_head('Arquivo não encontrado');
    echo '<div class="container"><div class="alert alert-danger">Arquivo não encontrado no GitHub.</div>';
    echo '<a href="/" class="btn btn-outline">← Início</a></div>';
    layout_foot();
    exit;
}

layout_head($titulo);
?>
<div class="container">
  <a href="/assunto.php?slug=<?= urlencode($assunto) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← Voltar</a>

  <div class="actions-bar">
    <div>
      <p class="page-title">📄 <?= htmlspecialchars($titulo) ?></p>
      <p class="page-sub"><?= htmlspecialchars($path) ?></p>
    </div>
  </div>

  <div class="card">
    <div class="md-content">
      <?= md_to_html($md) ?>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
