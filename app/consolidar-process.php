<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/ai.php';

header('Content-Type: application/json');
set_time_limit(180);

$slug = trim($_POST['slug'] ?? '');
if (!$slug) {
    echo json_encode(['ok' => false, 'erro' => 'Slug inválido.']);
    exit;
}

$nome    = ucwords(str_replace('-', ' ', $slug));
$uploads = gh_list_uploads($slug);

// Carrega contexto (problema + objetivo)
$ctx_raw = gh_get_content("assuntos/$slug/contexto.json");
$ctx     = $ctx_raw ? json_decode($ctx_raw, true) : [];

if (empty($uploads)) {
    echo json_encode(['ok' => false, 'erro' => 'Nenhum arquivo encontrado.']);
    exit;
}

// Carrega meta da consolidação anterior (quais arquivos já foram usados)
$meta_path       = "assuntos/$slug/consolidado/meta.json";
$meta_raw        = gh_get_content($meta_path);
$meta_anterior   = $meta_raw ? json_decode($meta_raw, true) : null;
$paths_anteriores = $meta_anterior['arquivos'] ?? [];

// Separa arquivos novos dos já consolidados
$arquivos_novos    = [];
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
    // Se há consolidado anterior e existem novos arquivos, faz consolidação incremental
    $consolidado_anterior = gh_get_content("assuntos/$slug/consolidado/resumo-final.md");

    if ($consolidado_anterior && !empty($paths_anteriores) && !empty($arquivos_novos)) {
        $resultado = ai_consolidar_incremental($nome, $consolidado_anterior, $arquivos_novos, $ctx);
    } else {
        // Primeira vez ou regerar tudo
        $todos = array_merge($arquivos_anteriores, $arquivos_novos);
        $resultado = ai_consolidar($nome, $todos, $ctx);
    }

    $md_final = $resultado . "\n\n---\n*Atualizado em $data via $provider ($modelo) · Unify*\n";

    // Salva o MD consolidado
    $ok = gh_save_file(
        "assuntos/$slug/consolidado/resumo-final.md",
        $md_final,
        "consolidado: atualiza resumo-final para $slug via $provider"
    );

    if (!$ok) {
        echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar consolidado no GitHub.']);
        exit;
    }

    // Salva meta.json com todos os arquivos que agora fazem parte do consolidado
    $todos_paths = array_merge(
        $paths_anteriores,
        array_column($arquivos_novos, 'path')
    );

    $meta_nova = [
        'arquivos'    => array_values(array_unique($todos_paths)),
        'gerado_em'   => date('c'),
        'modelo'      => $modelo,
        'provedor'    => strtolower($provider),
        'total'       => count(array_unique($todos_paths)),
    ];

    gh_save_file(
        $meta_path,
        json_encode($meta_nova, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        "meta: atualiza registro de arquivos consolidados em $slug"
    );

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
