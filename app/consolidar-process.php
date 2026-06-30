<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/ai.php';
require_once __DIR__ . '/lib/notas.php';

header('Content-Type: application/json');
set_time_limit(180);

$slug = trim($_POST['slug'] ?? '');
if (!$slug) {
    echo json_encode(['ok' => false, 'erro' => 'Slug inválido.']);
    exit;
}

$nome    = ucwords(str_replace('-', ' ', $slug));
$uploads = gh_list_uploads($slug);

if (empty($uploads)) {
    echo json_encode(['ok' => false, 'erro' => 'Nenhum arquivo encontrado.']);
    exit;
}

// Carrega contexto do assunto (problema + objetivo)
$ctx_raw = gh_get_content("assuntos/$slug/contexto.json");
$ctx     = $ctx_raw ? json_decode($ctx_raw, true) : [];

// Carrega configurações globais da Brudam
$global_raw    = gh_get_content('config/global.json');
$ctx['global'] = $global_raw ? json_decode($global_raw, true) : null;

// Carrega meta da consolidação anterior
$meta_path      = "assuntos/$slug/consolidado/meta.json";
$meta_raw       = gh_get_content($meta_path);
$meta_anterior  = $meta_raw ? json_decode($meta_raw, true) : null;
$paths_anteriores     = $meta_anterior['arquivos'] ?? [];
$ids_notas_anteriores = $meta_anterior['notas_ids'] ?? [];

// Todas as notas — separa novas das já consolidadas
$todas_notas  = notas_de_todos($slug);
$notas_novas  = array_values(array_filter($todas_notas, fn($n) => !in_array($n['id'], $ids_notas_anteriores)));
$ctx['notas'] = $todas_notas;

// Separa arquivos novos dos já consolidados
$arquivos_novos      = [];
$arquivos_anteriores = [];

foreach ($uploads as $f) {
    $conteudo = gh_get_content($f['path']);
    if ($conteudo === null) continue;

    $item = [
        'usuario'  => $f['usuario'],
        'arquivo'  => $f['arquivo'],
        'path'     => $f['path'],
        'conteudo' => $conteudo,
    ];

    if (in_array($f['path'], $paths_anteriores)) {
        $arquivos_anteriores[] = $item;
    } else {
        $arquivos_novos[] = $item;
    }
}

$provider = strtoupper(AI_PROVIDER);
$modelo   = AI_PROVIDER === 'claude' ? CLAUDE_MODEL : OPENAI_MODEL;
$data     = date('d/m/Y H:i');

try {
    $consolidado_anterior = gh_get_content("assuntos/$slug/consolidado/resumo-final.md");
    $tem_anterior         = $consolidado_anterior && !empty($paths_anteriores);
    $tem_novidades        = !empty($arquivos_novos) || !empty($notas_novas);

    if ($tem_anterior && $tem_novidades) {
        // INCREMENTAL: doc consolidado atual + só arquivos novos + só notas novas
        $resultado = ai_consolidar_incremental($nome, $consolidado_anterior, $arquivos_novos, $notas_novas, $ctx);
        $tipo_geracao = !empty($arquivos_novos) && !empty($notas_novas) ? 'incremental'
            : (!empty($arquivos_novos) ? 'incremental' : 'notas');
    } else {
        // PRIMEIRA VEZ: envia tudo
        $todos     = array_merge($arquivos_anteriores, $arquivos_novos);
        $resultado = ai_consolidar($nome, $todos, $ctx);
        $tipo_geracao = 'completo';
    }

    $md_final = $resultado . "\n\n---\n*Atualizado em $data via $provider ($modelo) · Unify*\n";

    $ok = gh_save_file(
        "assuntos/$slug/consolidado/resumo-final.md",
        $md_final,
        "consolidado: v" . (($meta_anterior['versao'] ?? 0) + 1) . " de $slug via $provider"
    );

    if (!$ok) {
        echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar consolidado no GitHub.']);
        exit;
    }

    // Atualiza meta.json com histórico
    $todos_paths = array_values(array_unique(array_merge(
        $paths_anteriores,
        array_column($arquivos_novos, 'path')
    )));

    $versao             = ($meta_anterior['versao'] ?? 0) + 1;
    $historico_anterior = $meta_anterior['historico'] ?? [];

    $historico_anterior[] = [
        'versao'         => $versao,
        'gerado_em'      => date('c'),
        'modelo'         => $modelo,
        'provedor'       => strtolower($provider),
        'total_arquivos' => count($todos_paths),
        'novos_arquivos' => count($arquivos_novos),
        'novas_notas'    => count($notas_novas),
        'tipo'           => $tipo_geracao,
    ];

    gh_save_file(
        $meta_path,
        json_encode([
            'arquivos'   => $todos_paths,
            'notas_ids'  => array_column($todas_notas, 'id'),
            'gerado_em'  => date('c'),
            'modelo'     => $modelo,
            'provedor'   => strtolower($provider),
            'total'      => count($todos_paths),
            'versao'     => $versao,
            'historico'  => $historico_anterior,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        "meta: versao $versao de $slug"
    );

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
