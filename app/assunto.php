<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect('/');

$nome    = ucwords(str_replace('-', ' ', $slug));
$uploads = gh_list_uploads($slug);
$novo    = isset($_GET['novo']);
$ok_msg  = $_GET['uploaded'] ?? '';

// agrupa por usuário
$por_usuario = [];
foreach ($uploads as $f) {
    $por_usuario[$f['usuario']][] = $f;
}

// verifica se já tem consolidado
$consolidado = gh_get_file("assuntos/$slug/consolidado/resumo-final.md");

layout_head($nome);
?>
<div class="container">
  <a href="/" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← Assuntos</a>

  <?php if ($novo): ?>
    <div class="alert alert-success">✅ Assunto <strong><?= htmlspecialchars($nome) ?></strong> criado com sucesso!</div>
  <?php endif; ?>
  <?php if ($ok_msg): ?>
    <div class="alert alert-success">✅ Arquivo <strong><?= htmlspecialchars($ok_msg) ?></strong> enviado com sucesso!</div>
  <?php endif; ?>

  <div class="actions-bar">
    <div>
      <p class="page-title">📋 <?= htmlspecialchars($nome) ?></p>
      <p class="page-sub"><?= count($uploads) ?> arquivo(s) · <?= count($por_usuario) ?> colaborador(es)</p>
    </div>
    <span class="spacer"></span>
    <a href="/upload.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline">📎 Enviar MD</a>
    <?php if (!empty($uploads)): ?>
      <form method="post" action="/consolidar.php" style="display:inline">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
        <button type="submit" class="btn btn-primary btn-lg" id="btn-consolidar"
          onclick="this.disabled=true;this.innerHTML='<span class=spinner></span> Gerando...'">
          ✨ Gerar Consolidado com IA
        </button>
      </form>
    <?php endif; ?>
  </div>

  <?php if ($consolidado): ?>
    <div class="alert alert-info" style="display:flex;align-items:center;gap:.75rem;">
      <span>📄</span>
      <span>Já existe um consolidado gerado para este assunto.</span>
      <span class="spacer" style="flex:1"></span>
      <a href="/resultado.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary btn-sm">Ver Resultado</a>
    </div>
  <?php endif; ?>

  <!-- Arquivos por usuário -->
  <?php if (empty($uploads)): ?>
    <div class="empty-state">
      <div class="icon">📂</div>
      <p>Nenhum arquivo enviado ainda.</p>
      <br>
      <a href="/upload.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary">Enviar primeiro MD</a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-header">
        <div class="card-icon">📁</div>
        <span class="card-title">Arquivos enviados</span>
      </div>

      <?php foreach ($por_usuario as $usuario => $arquivos): ?>
        <div class="user-group"><?= htmlspecialchars(ucfirst($usuario)) ?></div>
        <ul class="file-list">
          <?php foreach ($arquivos as $f): ?>
            <li class="file-item">
              <span class="icon">📄</span>
              <span class="name">
                <a href="/visualizar.php?path=<?= urlencode($f['path']) ?>&assunto=<?= urlencode($slug) ?>">
                  <?= htmlspecialchars($f['arquivo']) ?>
                </a>
              </span>
              <span class="user-badge"><?= htmlspecialchars(ucfirst($f['usuario'])) ?></span>
              <form method="post" action="/remover.php" style="display:inline"
                onsubmit="return confirm('Remover <?= htmlspecialchars($f['arquivo']) ?>?')">
                <input type="hidden" name="path" value="<?= htmlspecialchars($f['path']) ?>">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                <button type="submit" class="btn btn-sm" style="color:#dc2626;background:none;border:none;cursor:pointer;" title="Remover">🗑</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>
