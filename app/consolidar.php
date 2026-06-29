<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/ai.php';
require_once __DIR__ . '/lib/layout.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');

$slug = trim($_POST['slug'] ?? '');
if (!$slug) redirect('/');

$nome    = ucwords(str_replace('-', ' ', $slug));
$uploads = gh_list_uploads($slug);

if (empty($uploads)) {
    redirect("/assunto.php?slug=$slug");
}

// Busca conteúdo de cada arquivo
$arquivos = [];
foreach ($uploads as $f) {
    $conteudo = gh_get_content($f['path']);
    if ($conteudo !== null) {
        $arquivos[] = [
            'usuario'  => $f['usuario'],
            'arquivo'  => $f['arquivo'],
            'conteudo' => $conteudo,
        ];
    }
}

layout_head('Gerando consolidado — ' . $nome);
?>
<div class="container" style="max-width:520px;text-align:center;padding-top:4rem;">
  <div style="font-size:3rem;margin-bottom:1rem;">✨</div>
  <p class="page-title">Gerando consolidado com IA…</p>
  <p class="page-sub">Lendo <?= count($arquivos) ?> arquivo(s) e consolidando com <?= strtoupper(AI_PROVIDER) ?>.</p>
  <div style="margin-top:2rem;">
    <span class="spinner" style="border-color:rgba(37,99,235,.2);border-top-color:var(--primary);width:2rem;height:2rem;"></span>
  </div>
</div>

<script>
// Exibe spinner e chama o endpoint de processamento
setTimeout(function() {
  fetch('/consolidar-process.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'slug=<?= urlencode($slug) ?>'
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      window.location = '/resultado.php?slug=<?= urlencode($slug) ?>';
    } else {
      document.body.innerHTML = '<div class="container"><div class="alert alert-danger"><strong>Erro:</strong> ' + data.erro + '</div><a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline">← Voltar</a></div>';
    }
  })
  .catch(e => {
    document.body.innerHTML = '<div class="container"><div class="alert alert-danger">Erro de comunicação: ' + e.message + '</div></div>';
  });
}, 300);
</script>
<?php layout_foot(); ?>
