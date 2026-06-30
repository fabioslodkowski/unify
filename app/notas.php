<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/notas.php';

global $USUARIOS;

$slug    = trim($_GET['slug'] ?? $_POST['slug'] ?? '');
$usuario = trim($_GET['usuario'] ?? $_POST['usuario'] ?? '');
$editar_id = trim($_GET['editar'] ?? '');

if (!$slug) redirect('/');
if (!$usuario) redirect("/assunto.php?slug=$slug");

$nome_assunto = ucwords(str_replace('-', ' ', $slug));
$nome_usuario = $USUARIOS[$usuario] ?? ucfirst($usuario);
$erro         = '';
$sucesso      = '';

$notas = notas_carregar($slug, $usuario);

// ── Ações POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $tipo  = $_POST['tipo'] ?? '';
        $texto = trim($_POST['texto'] ?? '');

        if (!$texto || !isset(TIPOS_NOTA[$tipo])) {
            $erro = 'Preencha o texto e selecione o tipo.';
        } else {
            $notas[] = [
                'id'          => uniqid(),
                'tipo'        => $tipo,
                'texto'       => $texto,
                'criado_em'   => date('c'),
                'atualizado_em' => date('c'),
            ];
            if (notas_salvar($slug, $usuario, $notas)) {
                $sucesso = 'Nota adicionada.';
            } else {
                $erro = 'Erro ao salvar no GitHub.';
            }
        }
    }

    if ($acao === 'excluir') {
        $id    = $_POST['id'] ?? '';
        $notas = array_values(array_filter($notas, fn($n) => $n['id'] !== $id));
        if (notas_salvar($slug, $usuario, $notas)) {
            $sucesso = 'Nota removida.';
        } else {
            $erro = 'Erro ao remover no GitHub.';
        }
    }

    if ($acao === 'editar') {
        $id    = $_POST['id'] ?? '';
        $texto = trim($_POST['texto'] ?? '');
        $tipo  = $_POST['tipo'] ?? '';
        foreach ($notas as &$n) {
            if ($n['id'] === $id) {
                $n['texto']        = $texto;
                $n['tipo']         = $tipo;
                $n['atualizado_em'] = date('c');
            }
        }
        unset($n);
        if (notas_salvar($slug, $usuario, $notas)) {
            $sucesso = 'Nota atualizada.';
        } else {
            $erro = 'Erro ao salvar no GitHub.';
        }
    }
}

$por_tipo = notas_por_tipo($notas);

layout_head("Notas — $nome_usuario");
?>
<div class="container" style="max-width:680px">
  <a href="/assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">← <?= htmlspecialchars($nome_assunto) ?></a>

  <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <div>
      <p class="page-title">📝 Notas de <?= htmlspecialchars($nome_usuario) ?></p>
      <p class="page-sub">Contextos adicionais para o assunto <strong><?= htmlspecialchars($nome_assunto) ?></strong></p>
    </div>
    <span class="spacer" style="flex:1"></span>
    <span class="badge badge-gray"><?= count($notas) ?> nota<?= count($notas) !== 1 ? 's' : '' ?></span>
  </div>

  <?php if ($erro):   ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?><div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

  <!-- Formulário de nova nota -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
      <div class="card-icon">✏️</div>
      <span class="card-title">Adicionar nota</span>
    </div>
    <form method="post" id="form-nova-nota">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
      <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
      <input type="hidden" name="acao" value="adicionar">

      <!-- Seletor de tipo em cards -->
      <div class="form-group">
        <label>Tipo de contexto</label>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem;margin-top:.25rem;">
          <?php foreach (TIPOS_NOTA as $tipo => $cfg): ?>
            <label style="cursor:pointer;">
              <input type="radio" name="tipo" value="<?= $tipo ?>" style="display:none"
                onchange="selecionaTipo('<?= $tipo ?>')">
              <div class="tipo-card" id="tipo-<?= $tipo ?>"
                style="text-align:center;padding:.6rem .3rem;border-radius:8px;border:2px solid <?= $cfg['border'] ?>;background:<?= $cfg['bg'] ?>;transition:all .15s;font-size:.78rem;font-weight:500;color:<?= $cfg['cor'] ?>">
                <div style="font-size:1.2rem;margin-bottom:.2rem"><?= $cfg['icon'] ?></div>
                <?= $cfg['label'] ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="texto-nova">Texto da nota</label>
        <textarea id="texto-nova" name="texto" rows="3"
          placeholder="Descreva a observação, decisão, restrição, dúvida ou risco…"></textarea>
      </div>

      <button type="submit" class="btn btn-primary" id="btn-add-nota">+ Adicionar nota</button>
    </form>
  </div>

  <!-- Notas existentes por tipo -->
  <?php if (empty($notas)): ?>
    <div class="empty-state">
      <div class="icon">📝</div>
      <p>Nenhuma nota ainda. Adicione contextos acima.</p>
    </div>
  <?php else: ?>
    <?php foreach (TIPOS_NOTA as $tipo => $cfg):
      $lista = array_values($por_tipo[$tipo] ?? []);
      if (empty($lista)) continue;
    ?>
      <div style="margin-bottom:1.25rem;">
        <div class="section-divider" style="color:<?= $cfg['cor'] ?>">
          <?= $cfg['icon'] ?> <?= $cfg['label'] ?> (<?= count($lista) ?>)
        </div>

        <?php foreach ($lista as $nota): ?>
          <div class="card nota-card" id="nota-<?= $nota['id'] ?>"
            style="border-left:4px solid <?= $cfg['border'] ?>;background:<?= $cfg['bg'] ?>;margin-bottom:.6rem;padding:1rem 1.25rem;">

            <!-- modo leitura -->
            <div class="nota-view">
              <div style="display:flex;align-items:flex-start;gap:.75rem;">
                <span style="font-size:1.1rem;margin-top:.1rem"><?= $cfg['icon'] ?></span>
                <p style="flex:1;margin:0;font-size:.9rem;line-height:1.6;"><?= nl2br(htmlspecialchars($nota['texto'])) ?></p>
                <div style="display:flex;gap:.4rem;flex-shrink:0;">
                  <button onclick="editarNota('<?= $nota['id'] ?>')"
                    class="btn btn-outline btn-sm" title="Editar">✏️</button>
                  <form method="post" style="display:inline"
                    onsubmit="return confirm('Remover esta nota?')">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                    <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $nota['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626" title="Remover">🗑</button>
                  </form>
                </div>
              </div>
              <div style="font-size:.75rem;color:<?= $cfg['cor'] ?>;margin-top:.5rem;opacity:.7;">
                <?= date('d/m/Y H:i', strtotime($nota['criado_em'])) ?>
                <?php if ($nota['atualizado_em'] !== $nota['criado_em']): ?>
                  · editado <?= date('d/m/Y H:i', strtotime($nota['atualizado_em'])) ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- modo edição -->
            <div class="nota-edit" style="display:none;">
              <form method="post">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuario) ?>">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= $nota['id'] ?>">
                <div class="form-group" style="margin-bottom:.5rem;">
                  <select name="tipo" style="margin-bottom:.5rem;">
                    <?php foreach (TIPOS_NOTA as $t => $c): ?>
                      <option value="<?= $t ?>" <?= $t === $nota['tipo'] ? 'selected' : '' ?>><?= $c['icon'] ?> <?= $c['label'] ?></option>
                    <?php endforeach; ?>
                  </select>
                  <textarea name="texto" rows="3" style="margin-top:.4rem;"><?= htmlspecialchars($nota['texto']) ?></textarea>
                </div>
                <div style="display:flex;gap:.5rem;">
                  <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                  <button type="button" class="btn btn-outline btn-sm" onclick="cancelarEdicao('<?= $nota['id'] ?>')">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
var tipoSelecionado = null;

// Abre edição direto se veio com ?editar=id
<?php if ($editar_id): ?>
window.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('nota-<?= htmlspecialchars($editar_id) ?>');
  if (el) {
    editarNota('<?= htmlspecialchars($editar_id) ?>');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
<?php endif; ?>

function selecionaTipo(tipo) {
  document.querySelectorAll('.tipo-card').forEach(el => {
    el.style.transform = 'scale(1)';
    el.style.boxShadow = 'none';
  });
  var card = document.getElementById('tipo-' + tipo);
  if (card) {
    card.style.transform = 'scale(1.05)';
    card.style.boxShadow = '0 0 0 3px rgba(0,0,0,.15)';
  }
  tipoSelecionado = tipo;
}

function editarNota(id) {
  document.getElementById('nota-' + id).querySelector('.nota-view').style.display = 'none';
  document.getElementById('nota-' + id).querySelector('.nota-edit').style.display = 'block';
}

function cancelarEdicao(id) {
  document.getElementById('nota-' + id).querySelector('.nota-view').style.display = 'block';
  document.getElementById('nota-' + id).querySelector('.nota-edit').style.display = 'none';
}

document.getElementById('form-nova-nota').addEventListener('submit', function(e) {
  if (!tipoSelecionado) {
    e.preventDefault();
    alert('Selecione o tipo de nota antes de adicionar.');
  }
});
</script>
<?php layout_foot(); ?>
