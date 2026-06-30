<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome'] ?? '');
    $problema  = trim($_POST['problema'] ?? '');
    $objetivo  = trim($_POST['objetivo'] ?? '');

    if (!$nome) {
        $erro = 'Informe o nome do assunto.';
    } else {
        $slug = slugify($nome);

        // Cria pasta de uploads
        gh_save_file("assuntos/$slug/uploads/.gitkeep", '', "feat: cria assunto $slug");

        // Salva contexto (problema + objetivo)
        $contexto = json_encode([
            'nome'      => $nome,
            'problema'  => $problema,
            'objetivo'  => $objetivo,
            'criado_em' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $ok = gh_save_file("assuntos/$slug/contexto.json", $contexto, "feat: define contexto de $slug");

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

      <div class="form-group">
        <label for="problema">Problema a resolver <span style="color:var(--gray-600);font-weight:400">(opcional)</span></label>
        <textarea id="problema" name="problema" rows="3"
          placeholder="Descreva o problema, dor ou situação atual que motivou este assunto…"></textarea>
      </div>

      <div class="form-group">
        <label for="objetivo">Objetivo <span style="color:var(--gray-600);font-weight:400">(opcional)</span></label>
        <textarea id="objetivo" name="objetivo" rows="3"
          placeholder="O que se espera alcançar, decidir ou entender ao final desta análise…"></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Criar Assunto</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
