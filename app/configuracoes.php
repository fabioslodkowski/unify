<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

const CONFIG_PATH = 'config/global.json';

$erro   = '';
$sucesso = '';

$raw    = gh_get_content(CONFIG_PATH);
$cfg    = $raw ? json_decode($raw, true) : ['contexto' => '', 'regras' => []];
$regras = $cfg['regras'] ?? [];

// ── Ações POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_contexto') {
        $cfg['contexto']      = trim($_POST['contexto'] ?? '');
        $cfg['atualizado_em'] = date('c');
        if (gh_save_file(CONFIG_PATH, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'config: atualiza contexto global')) {
            $sucesso = 'Contexto salvo.';
        } else {
            $erro = 'Erro ao salvar no GitHub.';
        }
    }

    if ($acao === 'add_regra') {
        $texto = trim($_POST['texto'] ?? '');
        if ($texto) {
            $cfg['regras'][]      = ['id' => uniqid(), 'texto' => $texto, 'ativo' => true, 'criado_em' => date('c')];
            $cfg['atualizado_em'] = date('c');
            if (gh_save_file(CONFIG_PATH, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'config: adiciona regra global')) {
                $sucesso = 'Regra adicionada.';
            } else {
                $erro = 'Erro ao salvar no GitHub.';
            }
        } else {
            $erro = 'Informe o texto da regra.';
        }
    }

    if ($acao === 'toggle_regra') {
        $id = $_POST['id'] ?? '';
        foreach ($cfg['regras'] as &$r) {
            if ($r['id'] === $id) $r['ativo'] = !($r['ativo'] ?? true);
        }
        unset($r);
        $cfg['atualizado_em'] = date('c');
        gh_save_file(CONFIG_PATH, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'config: toggle regra');
        $sucesso = 'Regra atualizada.';
    }

    if ($acao === 'editar_regra') {
        $id    = $_POST['id'] ?? '';
        $texto = trim($_POST['texto'] ?? '');
        foreach ($cfg['regras'] as &$r) {
            if ($r['id'] === $id) $r['texto'] = $texto;
        }
        unset($r);
        $cfg['atualizado_em'] = date('c');
        gh_save_file(CONFIG_PATH, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'config: edita regra');
        $sucesso = 'Regra atualizada.';
    }

    if ($acao === 'excluir_regra') {
        $id = $_POST['id'] ?? '';
        $cfg['regras'] = array_values(array_filter($cfg['regras'], fn($r) => $r['id'] !== $id));
        $cfg['atualizado_em'] = date('c');
        gh_save_file(CONFIG_PATH, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'config: remove regra');
        $sucesso = 'Regra removida.';
    }

    $regras = $cfg['regras'] ?? [];
}

$ativas   = count(array_filter($regras, fn($r) => $r['ativo'] ?? true));
$inativas = count($regras) - $ativas;

layout_head('Configurações');
?>
<div class="container" style="max-width:700px">
  <div style="margin-bottom:1.5rem;">
    <p class="page-title">⚙️ Configurações</p>
    <p class="page-sub">Contexto institucional e regras da Brudam considerados na geração de todos os consolidados.</p>
  </div>

  <?php if ($erro):    ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?><div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

  <!-- Contexto geral -->
  <div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
      <div class="card-icon">🏢</div>
      <span class="card-title">Contexto Geral da Brudam</span>
    </div>
    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:.75rem;">
      Descreva a empresa, mercado de atuação, valores, restrições estruturais ou qualquer contexto
      que a IA deve ter em mente ao analisar <strong>qualquer assunto</strong> — ela aplicará apenas quando pertinente.
    </p>
    <form method="post">
      <input type="hidden" name="acao" value="salvar_contexto">
      <div class="form-group">
        <textarea name="contexto" rows="6"
          placeholder="Ex: A Brudam é uma empresa de tecnologia para o setor de logística. Nossas soluções são B2B, com clientes de médio e grande porte. Priorizamos compliance, segurança de dados (LGPD) e integrações via API REST. O stack principal é Laravel + MySQL. Evitamos soluções que exijam alto custo de infraestrutura…"><?= htmlspecialchars($cfg['contexto'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Salvar contexto</button>
    </form>
  </div>

  <!-- Regras e diretrizes -->
  <div class="card">
    <div class="card-header">
      <div class="card-icon">📋</div>
      <span class="card-title">Regras & Diretrizes</span>
      <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
        <?php if ($ativas):   ?><span class="badge badge-green"><?= $ativas ?> ativa<?= $ativas   !== 1 ? 's' : '' ?></span><?php endif; ?>
        <?php if ($inativas): ?><span class="badge badge-gray"><?= $inativas ?> inativa<?= $inativas !== 1 ? 's' : '' ?></span><?php endif; ?>
      </div>
    </div>

    <p style="font-size:.82rem;color:var(--gray-600);margin-bottom:1rem;">
      Regras específicas que a IA deve seguir. Desative temporariamente sem excluir.
    </p>

    <!-- Nova regra -->
    <form method="post" style="display:flex;gap:.5rem;margin-bottom:1.25rem;">
      <input type="hidden" name="acao" value="add_regra">
      <input type="text" name="texto" placeholder="Ex: Sempre avaliar impacto em LGPD antes de recomendar coleta de dados" style="flex:1">
      <button type="submit" class="btn btn-primary" style="white-space:nowrap">+ Adicionar</button>
    </form>

    <!-- Lista de regras -->
    <?php if (empty($regras)): ?>
      <p style="text-align:center;color:var(--gray-600);font-size:.875rem;padding:.5rem 0;">
        Nenhuma regra cadastrada ainda.
      </p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:.5rem;">
        <?php foreach ($regras as $r):
          $ativo = $r['ativo'] ?? true;
        ?>
          <div id="regra-<?= $r['id'] ?>" style="border:1px solid var(--gray-200);border-radius:8px;
            padding:.75rem 1rem;background:<?= $ativo ? '#fff' : '#f9fafb' ?>;
            opacity:<?= $ativo ? '1' : '.6' ?>;transition:all .15s;">

            <!-- Modo leitura -->
            <div class="regra-view" style="display:flex;align-items:flex-start;gap:.75rem;">
              <!-- Toggle ativo -->
              <form method="post" style="flex-shrink:0;margin-top:.1rem;">
                <input type="hidden" name="acao" value="toggle_regra">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" title="<?= $ativo ? 'Desativar' : 'Ativar' ?>"
                  style="background:none;border:none;cursor:pointer;font-size:1.1rem;padding:0;line-height:1;">
                  <?= $ativo ? '🟢' : '⚫' ?>
                </button>
              </form>

              <span style="flex:1;font-size:.875rem;line-height:1.6;color:<?= $ativo ? 'var(--gray-800)' : 'var(--gray-600)' ?>;">
                <?= htmlspecialchars($r['texto']) ?>
              </span>

              <div style="display:flex;gap:.35rem;flex-shrink:0;">
                <button onclick="editarRegra('<?= $r['id'] ?>')" class="btn btn-outline btn-sm" title="Editar">✏️</button>
                <form method="post" style="display:inline" onsubmit="return confirm('Excluir esta regra?')">
                  <input type="hidden" name="acao" value="excluir_regra">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626" title="Excluir">🗑</button>
                </form>
              </div>
            </div>

            <!-- Modo edição -->
            <div class="regra-edit" style="display:none;">
              <form method="post" style="display:flex;gap:.5rem;align-items:flex-start;">
                <input type="hidden" name="acao" value="editar_regra">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="text" name="texto" value="<?= htmlspecialchars($r['texto']) ?>" style="flex:1">
                <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="cancelarRegra('<?= $r['id'] ?>')">✕</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <p style="font-size:.78rem;color:var(--gray-600);margin-top:1rem;text-align:center;">
    Contexto e regras são salvos no GitHub em <code>config/global.json</code> e aplicados em todos os consolidados.
    <?php if (!empty($cfg['atualizado_em'])): ?>
      Última atualização: <?= date('d/m/Y H:i', strtotime($cfg['atualizado_em'])) ?>.
    <?php endif; ?>
  </p>
</div>

<script>
function editarRegra(id) {
  var el = document.getElementById('regra-' + id);
  el.querySelector('.regra-view').style.display = 'none';
  el.querySelector('.regra-edit').style.display = 'block';
  el.querySelector('.regra-edit input[type=text]').focus();
}
function cancelarRegra(id) {
  var el = document.getElementById('regra-' + id);
  el.querySelector('.regra-view').style.display = 'flex';
  el.querySelector('.regra-edit').style.display = 'none';
}
</script>
<?php layout_foot(); ?>
