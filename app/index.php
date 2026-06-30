<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$assuntos = gh_list_assuntos();

layout_head('Assuntos');
?>
<div class="container">
  <div class="actions-bar">
    <div>
      <p class="page-title">📂 Assuntos</p>
      <p class="page-sub">Selecione um assunto para ver os arquivos ou crie um novo.</p>
    </div>
    <span class="spacer"></span>
    <a href="/novo-assunto.php" class="btn btn-primary">+ Novo Assunto</a>
  </div>

  <?php if (isset($_GET['excluido'])): ?>
    <div class="alert alert-success">🗑 Assunto <strong><?= htmlspecialchars($_GET['excluido']) ?></strong> excluído com sucesso.</div>
  <?php endif; ?>

  <?php if (empty($assuntos)): ?>
    <div class="empty-state">
      <div class="icon">📭</div>
      <p>Nenhum assunto criado ainda.</p>
      <br>
      <a href="/novo-assunto.php" class="btn btn-primary">Criar primeiro assunto</a>
    </div>
  <?php else: ?>
    <?php foreach ($assuntos as $a): ?>
      <?php
        $slug   = $a['name'];
        $nome   = str_replace('-', ' ', ucfirst($slug));
        $uploads = gh_list_uploads($slug);
        $total  = count($uploads);
        $users  = count(array_unique(array_column($uploads, 'usuario')));
      ?>
      <a href="/assunto.php?slug=<?= htmlspecialchars($slug) ?>" style="display:block;text-decoration:none;color:inherit;">
        <div class="assunto-card">
          <div style="font-size:1.5rem;">📋</div>
          <div>
            <div class="nome"><?= htmlspecialchars(ucwords($nome)) ?></div>
            <div class="meta">
              <?= $total ?> arquivo<?= $total !== 1 ? 's' : '' ?>
              &nbsp;·&nbsp;
              <?= $users ?> colaborador<?= $users !== 1 ? 'es' : '' ?>
            </div>
          </div>
          <div class="arrow">›</div>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>
