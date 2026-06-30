<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$slug = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
if (!$slug) redirect('/');

$nome  = ucwords(str_replace('-', ' ', $slug));
$erro  = '';

$ctx_raw  = gh_get_content("assuntos/$slug/contexto.json");
$contexto = $ctx_raw ? json_decode($ctx_raw, true) : ['nome' => $nome, 'problema' => '', 'objetivo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contexto['problema']    = trim($_POST['problema'] ?? '');
    $contexto['objetivo']    = trim($_POST['objetivo'] ?? '');
    $contexto['atualizado_em'] = date('c');

    $ok = gh_save_file(
        "assuntos/$slug/contexto.json",
        json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        "update: contexto de $slug"
    );

    if ($ok) {
        redirect("/assunto.php?slug=$slug");
    } else {
        $erro = 'Erro ao salvar no GitHub.';
    }
}

layout_head('Editar Contexto — ' . $nome);
?>
<div class="container" style="max-width:520px">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome) ?></a>

  <p class="page-title">🎯 Contexto do assunto</p>
  <p class="page-sub"><strong><?= htmlspecialchars($nome) ?></strong></p>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">

      <div class="form-group">
        <label for="problema">Problema a resolver</label>
        <textarea id="problema" name="problema" rows="4"
          placeholder="Descreva o problema, dor ou situação atual…"><?= htmlspecialchars($contexto['problema'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="objetivo">Objetivo</label>
        <textarea id="objetivo" name="objetivo" rows="4"
          placeholder="O que se espera alcançar, decidir ou entender…"><?= htmlspecialchars($contexto['objetivo'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
