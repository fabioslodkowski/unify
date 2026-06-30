<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
if (!$slug) redirect('/');

$nome = ucwords(str_replace('-', ' ', $slug));

// POST = executa exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivos = gh_list_all_files("assuntos/$slug");
    $erros    = [];

    foreach ($arquivos as $f) {
        $ok = gh_delete_file($f['path'], "remove: exclui assunto $slug");
        if (!$ok) $erros[] = $f['path'];
    }

    if (empty($erros)) {
        redirect('/?excluido=' . urlencode($nome));
    } else {
        layout_head('Erro na exclusão');
        echo '<div class="container">';
        echo '<div class="alert alert-danger"><strong>Alguns arquivos não puderam ser removidos:</strong><br>' . implode('<br>', array_map('htmlspecialchars', $erros)) . '</div>';
        echo '<a href="/" class="btn btn-outline">← Início</a>';
        echo '</div>';
        layout_foot();
        exit;
    }
}

// GET = tela de confirmação
$arquivos = gh_list_all_files("assuntos/$slug");

layout_head('Excluir — ' . $nome);
?>
<div class="container" style="max-width:560px">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome) ?></a>

  <div class="card" style="border-color:#fecaca;background:#fef2f2;">
    <div class="card-header" style="border-color:#fecaca;">
      <div class="card-icon" style="background:#fee2e2;font-size:1.3rem;">🗑</div>
      <span class="card-title" style="color:#b91c1c;">Excluir assunto</span>
    </div>

    <p style="margin-bottom:1rem;">
      Você está prestes a excluir o assunto <strong><?= htmlspecialchars($nome) ?></strong> e
      <strong>todos os <?= count($arquivos) ?> arquivo(s)</strong> relacionados do GitHub.
      <br><br>
      <strong style="color:#b91c1c;">Esta ação não pode ser desfeita.</strong>
    </p>

    <?php if (!empty($arquivos)): ?>
      <details style="margin-bottom:1rem;">
        <summary style="cursor:pointer;font-size:.85rem;color:var(--gray-600);user-select:none;">
          Ver <?= count($arquivos) ?> arquivo(s) que serão removidos
        </summary>
        <ul style="margin-top:.5rem;padding-left:1.25rem;font-size:.82rem;color:var(--gray-600);line-height:1.8;">
          <?php foreach ($arquivos as $f): ?>
            <li><?= htmlspecialchars($f['path']) ?></li>
          <?php endforeach; ?>
        </ul>
      </details>
    <?php endif; ?>

    <div style="display:flex;gap:.75rem;">
      <form method="post">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
        <button type="submit" class="btn btn-danger"
          onclick="var b=this;setTimeout(function(){b.disabled=true;b.innerHTML='<span class=spinner></span> Excluindo...';},10);">
          Sim, excluir tudo
        </button>
      </form>
      <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline">Cancelar</a>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
