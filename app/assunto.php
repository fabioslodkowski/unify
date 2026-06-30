<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/notas.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect('/');

$nome    = ucwords(str_replace('-', ' ', $slug));
$uploads = gh_list_uploads($slug);

// Carrega contexto do assunto
$ctx_raw  = gh_get_content("assuntos/$slug/contexto.json");
$contexto = $ctx_raw ? json_decode($ctx_raw, true) : null;

// Carrega todas as notas do assunto
$todas_notas = notas_de_todos($slug);
$notas_por_tipo = notas_por_tipo($todas_notas);

// Carrega meta do consolidado para marcar arquivos já incluídos
$meta_raw        = gh_get_content("assuntos/$slug/consolidado/meta.json");
$meta            = $meta_raw ? json_decode($meta_raw, true) : null;
$paths_consolidados = $meta['arquivos'] ?? [];
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
      <?php
        $total_arqs = count($uploads);
        $novos_arqs = count(array_filter($uploads, fn($f) => !in_array($f['path'], $paths_consolidados)));
        $ids_notas_consolidadas = $meta['notas_ids'] ?? [];
        $ids_notas_atuais       = array_column($todas_notas, 'id');
        $notas_novas            = count(array_diff($ids_notas_atuais, $ids_notas_consolidadas));
      ?>
      <p class="page-sub">
        <?= $total_arqs ?> arquivo(s) · <?= count($por_usuario) ?> colaborador(es)
        <?php if ($novos_arqs > 0 || $notas_novas > 0): ?>
          · <span style="color:var(--primary);font-weight:600">
            <?php $partes = []; ?>
            <?php if ($novos_arqs > 0) $partes[] = "$novos_arqs arquivo(s) novo(s)"; ?>
            <?php if ($notas_novas > 0) $partes[] = "$notas_novas nota(s) nova(s)"; ?>
            <?= implode(' e ', $partes) ?> desde o último consolidado
          </span>
        <?php endif; ?>
      </p>
    </div>
    <span class="spacer"></span>
    <a href="/upload.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline">📎 Enviar MD</a>
    <a href="/excluir-assunto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline" style="color:#dc2626;border-color:#fca5a5;" title="Excluir assunto">🗑 Excluir</a>
    <?php if (!empty($uploads)): ?>
      <?php
        $tem_consolidado = (bool) $consolidado;
        $tem_novos       = $novos_arqs > 0 || $notas_novas > 0;
        $pode_gerar      = !$tem_consolidado || $tem_novos;
      ?>
      <?php if ($pode_gerar): ?>
        <form method="post" action="/consolidar.php" style="display:inline">
          <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
          <button type="submit" class="btn btn-primary btn-lg" id="btn-consolidar"
            onclick="var b=this;setTimeout(function(){b.disabled=true;b.innerHTML='<span class=spinner></span> Gerando...';},10);">
            ✨ <?php
            if (!$tem_consolidado) echo 'Gerar Consolidado com IA';
            elseif ($novos_arqs > 0 && $notas_novas > 0) echo 'Atualizar com novos arquivos e notas';
            elseif ($novos_arqs > 0) echo 'Atualizar com novos arquivos';
            else echo 'Atualizar com novas notas';
          ?>
          </button>
        </form>
      <?php else: ?>
        <div style="display:inline-flex;align-items:center;gap:.5rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:8px;padding:.6rem 1rem;font-size:.875rem;font-weight:500;">
          ✅ Consolidado em dia — envie arquivos ou adicione notas para atualizar
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Contexto do assunto -->
  <?php if ($contexto && ($contexto['problema'] || $contexto['objetivo'])): ?>
    <div class="card" style="margin-bottom:1rem;">
      <div class="card-header">
        <div class="card-icon">🎯</div>
        <span class="card-title">Contexto do assunto</span>
        <a href="/editar-contexto.php?slug=<?= urlencode($slug) ?>" class="btn btn-outline btn-sm" style="margin-left:auto">Editar</a>
      </div>
      <?php if ($contexto['problema']): ?>
        <p style="font-size:.875rem;margin-bottom:.5rem;"><strong>Problema:</strong> <?= nl2br(htmlspecialchars($contexto['problema'])) ?></p>
      <?php endif; ?>
      <?php if ($contexto['objetivo']): ?>
        <p style="font-size:.875rem;margin:0"><strong>Objetivo:</strong> <?= nl2br(htmlspecialchars($contexto['objetivo'])) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($consolidado && $meta): ?>
    <?php
      $versao    = $meta['versao'] ?? 1;
      $historico = $meta['historico'] ?? [];
      $ultimo    = end($historico) ?: $meta;
      $data_ger  = isset($ultimo['gerado_em']) ? date('d/m/Y H:i', strtotime($ultimo['gerado_em'])) : '—';
      $tipo_icon = ['completo' => '🆕', 'incremental' => '➕', 'regeneado' => '🔄'][$ultimo['tipo'] ?? ''] ?? '📄';
    ?>
    <div class="card" style="border-color:#bfdbfe;background:#eff6ff;margin-bottom:1rem;">
      <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div style="font-size:1.5rem;">📄</div>
        <div style="flex:1">
          <div style="font-weight:600;font-size:.95rem;">Consolidado disponível</div>
          <div style="font-size:.82rem;color:var(--gray-600);">
            Última geração: <?= $data_ger ?> · <?= strtoupper($ultimo['provedor'] ?? '') ?> (<?= htmlspecialchars($ultimo['modelo'] ?? '') ?>)
          </div>
        </div>
        <span class="badge badge-blue" style="font-size:.85rem;padding:.3rem .75rem;">
          v<?= $versao ?> · <?= $versao ?> <?= $versao === 1 ? 'geração' : 'gerações' ?>
        </span>
        <a href="/resultado.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary btn-sm">Ver Resultado</a>
      </div>

      <?php if (count($historico) > 1): ?>
        <details style="margin-top:.75rem;border-top:1px solid #bfdbfe;padding-top:.75rem;">
          <summary style="cursor:pointer;font-size:.82rem;color:var(--primary);font-weight:500;user-select:none;">
            Ver histórico de <?= count($historico) ?> gerações
          </summary>
          <table style="width:100%;margin-top:.5rem;font-size:.8rem;border-collapse:collapse;">
            <thead>
              <tr style="color:var(--gray-600);text-align:left;border-bottom:1px solid #bfdbfe;">
                <th style="padding:.3rem .5rem">Versão</th>
                <th style="padding:.3rem .5rem">Data</th>
                <th style="padding:.3rem .5rem">Tipo</th>
                <th style="padding:.3rem .5rem">Arquivos</th>
                <th style="padding:.3rem .5rem">Modelo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($historico) as $h): ?>
                <tr style="border-bottom:1px solid #e0effe;">
                  <td style="padding:.3rem .5rem;font-weight:600;">v<?= $h['versao'] ?></td>
                  <td style="padding:.3rem .5rem;"><?= date('d/m/Y H:i', strtotime($h['gerado_em'])) ?></td>
                  <td style="padding:.3rem .5rem;">
                    <?php $ti = ['completo'=>'🆕 Completo','incremental'=>'➕ Incremental','regeneado'=>'🔄 Regeneado'][$h['tipo'] ?? ''] ?? '📄'; ?>
                    <?= $ti ?>
                  </td>
                  <td style="padding:.3rem .5rem;"><?= $h['total_arquivos'] ?> <?= ($h['novos_arquivos'] ?? 0) > 0 ? "(+{$h['novos_arquivos']} novos)" : '' ?></td>
                  <td style="padding:.3rem .5rem;color:var(--gray-600)"><?= htmlspecialchars($h['modelo'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </details>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Seção de Notas e Contextos -->
  <div class="card" style="margin-bottom:1rem;">
    <div class="card-header">
      <div class="card-icon">📝</div>
      <span class="card-title">Notas & Contextos</span>
      <span class="badge badge-gray" style="margin-left:.5rem"><?= count($todas_notas) ?></span>
      <span style="flex:1"></span>
      <?php foreach ($USUARIOS as $u_slug => $u_nome): ?>
        <a href="/notas.php?slug=<?= urlencode($slug) ?>&usuario=<?= urlencode($u_slug) ?>"
          class="btn btn-outline btn-sm" style="margin-left:.35rem;">✏️ <?= htmlspecialchars($u_nome) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($todas_notas)): ?>
      <p style="color:var(--gray-600);font-size:.875rem;text-align:center;padding:.75rem 0;">
        Nenhuma nota adicionada. Clique em um usuário para adicionar contextos.
      </p>
    <?php else: ?>
      <?php foreach (TIPOS_NOTA as $tipo => $cfg):
        $lista = array_values($notas_por_tipo[$tipo] ?? []);
        if (empty($lista)) continue;
      ?>
        <div style="margin-bottom:.75rem;">
          <div style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:600;
            text-transform:uppercase;letter-spacing:.06em;color:<?= $cfg['cor'] ?>;margin-bottom:.35rem;">
            <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
            <span style="font-weight:400;opacity:.7">(<?= count($lista) ?>)</span>
          </div>
          <?php foreach ($lista as $n): ?>
            <?php $nota_consolidada = in_array($n['id'], $ids_notas_consolidadas); ?>
            <a href="/notas.php?slug=<?= urlencode($slug) ?>&usuario=<?= urlencode($n['usuario']) ?>&editar=<?= urlencode($n['id']) ?>"
              style="display:flex;align-items:flex-start;gap:.6rem;padding:.45rem .6rem;
              border-radius:8px;background:<?= $cfg['bg'] ?>;border:1px solid <?= $cfg['border'] ?>;
              margin-bottom:.35rem;text-decoration:none;color:inherit;transition:filter .15s;
              <?= $nota_consolidada ? 'opacity:.65;' : '' ?>"
              onmouseover="this.style.filter='brightness(.96)'" onmouseout="this.style.filter=''">
              <span style="font-size:.85rem;margin-top:.05rem"><?= $nota_consolidada ? '✅' : '🆕' ?></span>
              <span style="font-size:.875rem;flex:1;line-height:1.5;"><?= nl2br(htmlspecialchars($n['texto'])) ?></span>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.2rem;flex-shrink:0;">
                <?php if ($nota_consolidada): ?>
                  <span style="font-size:.7rem;background:#dcfce7;color:#15803d;border-radius:20px;padding:.1rem .45rem;font-weight:600;">consolidada</span>
                <?php else: ?>
                  <span style="font-size:.7rem;background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:.1rem .45rem;font-weight:600;">nova</span>
                <?php endif; ?>
                <span style="font-size:.72rem;color:<?= $cfg['cor'] ?>;opacity:.7;white-space:nowrap;">
                  <?= htmlspecialchars(ucfirst($n['usuario'])) ?> ✏️
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

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
            <?php $ja_consolidado = in_array($f['path'], $paths_consolidados); ?>
            <li class="file-item">
              <span class="icon"><?= $ja_consolidado ? '✅' : '🆕' ?></span>
              <span class="name">
                <a href="/visualizar.php?path=<?= urlencode($f['path']) ?>&assunto=<?= urlencode($slug) ?>">
                  <?= htmlspecialchars($f['arquivo']) ?>
                </a>
              </span>
              <?php if ($ja_consolidado): ?>
                <span class="badge badge-green" title="Incluído no último consolidado">consolidado</span>
              <?php else: ?>
                <span class="badge badge-blue" title="Ainda não incluído no consolidado">novo</span>
              <?php endif; ?>
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
