<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/ai.php';

header('Content-Type: application/json');

// Aumenta timeout para chamadas de IA
set_time_limit(120);

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

try {
    $resultado = ai_consolidar($nome, $arquivos);

    $provider = strtoupper(AI_PROVIDER);
    $modelo   = AI_PROVIDER === 'claude' ? CLAUDE_MODEL : OPENAI_MODEL;
    $data     = date('d/m/Y H:i');

    $md_final = $resultado . "\n\n---\n*Gerado em $data via $provider ($modelo) · Unify*\n";

    $ok = gh_save_file(
        "assuntos/$slug/consolidado/resumo-final.md",
        $md_final,
        "consolidado: gera resumo-final para $slug via $provider"
    );

    if (!$ok) {
        echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar no GitHub.']);
        exit;
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
