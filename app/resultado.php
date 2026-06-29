<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/layout.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect('/');

$nome = ucwords(str_replace('-', ' ', $slug));
$md   = gh_get_content("assuntos/$slug/consolidado/resumo-final.md");

if (!$md) {
    redirect("/assunto.php?slug=$slug");
}

$sections  = parse_sections($md);
$data_ger  = date('d/m/Y');

// ícones e labels por tipo de seção
$config_sec = [
    'executivo'    => ['icon' => '🎯', 'label' => 'Resumo Executivo'],
    'contexto'     => ['icon' => '📋', 'label' => 'Contexto'],
    'pontos'       => ['icon' => '💡', 'label' => 'Principais Pontos'],
    'consenso'     => ['icon' => '✅', 'label' => 'Pontos de Consenso'],
    'divergentes'  => ['icon' => '⚡', 'label' => 'Pontos Divergentes'],
    'riscos'       => ['icon' => '⚠️', 'label' => 'Riscos Identificados'],
    'custos'       => ['icon' => '💰', 'label' => 'Custos e Impactos'],
    'duvidas'      => ['icon' => '❓', 'label' => 'Dúvidas em Aberto'],
    'recomendacao' => ['icon' => '🏆', 'label' => 'Recomendação Inicial'],
    'proximos'     => ['icon' => '🚀', 'label' => 'Próximos Passos'],
    'fontes'       => ['icon' => '📚', 'label' => 'Fontes Utilizadas'],
    'generico'     => ['icon' => '📄', 'label' => ''],
];

layout_head('Resultado — ' . $nome);
?>
<div class="container-wide">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome) ?></a>

  <!-- Header do resultado -->
  <div class="resultado-header">
    <h1>📄 Resumo Consolidado</h1>
    <div class="meta"><?= htmlspecialchars($nome) ?> &nbsp;·&nbsp; Gerado em <?= $data_ger ?> &nbsp;·&nbsp; <?= strtoupper(AI_PROVIDER) ?></div>
  </div>

  <!-- Barra de ações -->
  <div class="actions-bar" style="margin-bottom:1.5rem;">
    <a href="/download.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline">⬇️ Baixar .md</a>
    <a href="/resultado.php?slug=<?= urlencode($slug) ?>&raw=1" class="btn btn-outline">👁 Ver bruto</a>
    <span class="spacer"></span>
    <form method="post" action="/consolidar.php" style="display:inline">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
      <button type="submit" class="btn btn-outline"
        onclick="this.disabled=true;this.innerHTML='<span class=spinner></span> Gerando...'">
        🔄 Gerar novamente
      </button>
    </form>
  </div>

  <?php if (isset($_GET['raw'])): ?>
    <!-- Modo bruto -->
    <div class="card">
      <pre style="white-space:pre-wrap;font-size:.85rem;font-family:'Fira Mono',monospace;line-height:1.6;"><?= htmlspecialchars($md) ?></pre>
    </div>
  <?php else: ?>
    <!-- Seções renderizadas -->
    <?php foreach ($sections as $sec):
      $tipo = $sec['tipo'];
      $cfg  = $config_sec[$tipo] ?? $config_sec['generico'];
      $icon = $cfg['icon'];
    ?>

      <?php if ($tipo === 'intro'): ?>
        <?php if (trim($sec['conteudo'])): ?>
          <div class="card" style="background:#eff6ff;border-color:#bfdbfe;">
            <div class="md-content"><?= md_to_html($sec['conteudo']) ?></div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="card sec-<?= htmlspecialchars($tipo) ?>">
          <div class="card-header">
            <div class="card-icon"><?= $icon ?></div>
            <span class="card-title"><?= htmlspecialchars($sec['titulo']) ?></span>
          </div>
          <div class="md-content">
            <?= md_to_html($sec['conteudo']) ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php layout_foot(); ?>
