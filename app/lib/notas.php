<?php

const TIPOS_NOTA = [
    'decisao'    => ['label' => 'Decisão',    'icon' => '✅', 'cor' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
    'restricao'  => ['label' => 'Restrição',  'icon' => '🚫', 'cor' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca'],
    'observacao' => ['label' => 'Observação', 'icon' => '💡', 'cor' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
    'duvida'     => ['label' => 'Dúvida',     'icon' => '❓', 'cor' => '#0891b2', 'bg' => '#f0f9ff', 'border' => '#bae6fd'],
    'risco'      => ['label' => 'Risco',      'icon' => '⚠️', 'cor' => '#ea580c', 'bg' => '#fff7ed', 'border' => '#fed7aa'],
];

function notas_path(string $slug, string $usuario): string
{
    return "assuntos/$slug/notas/$usuario.json";
}

function notas_carregar(string $slug, string $usuario): array
{
    $raw = gh_get_content(notas_path($slug, $usuario));
    return $raw ? json_decode($raw, true) : [];
}

function notas_salvar(string $slug, string $usuario, array $notas): bool
{
    return gh_save_file(
        notas_path($slug, $usuario),
        json_encode($notas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        "notas: atualiza contextos de $usuario em $slug"
    );
}

function notas_de_todos(string $slug): array
{
    $arquivos = gh_list_dir("assuntos/$slug/notas");
    $resultado = [];
    foreach ($arquivos as $f) {
        if ($f['type'] !== 'file' || !str_ends_with($f['name'], '.json')) continue;
        $usuario = str_replace('.json', '', $f['name']);
        $raw     = gh_get_content("assuntos/$slug/notas/$usuario.json");
        if (!$raw) continue;
        $notas = json_decode($raw, true) ?? [];
        foreach ($notas as $n) {
            $resultado[] = array_merge($n, ['usuario' => $usuario]);
        }
    }
    // ordena por tipo depois data
    usort($resultado, fn($a, $b) =>
        strcmp($a['tipo'], $b['tipo']) ?: strcmp($a['criado_em'], $b['criado_em'])
    );
    return $resultado;
}

function notas_para_prompt(array $notas): string
{
    if (empty($notas)) return '';

    $por_tipo = notas_por_tipo($notas);
    $labels   = [
        'decisao'    => 'Decisões já tomadas pela equipe (considere como fatos consolidados)',
        'restricao'  => 'Restrições obrigatórias (devem ser respeitadas na análise)',
        'observacao' => 'Observações da equipe',
        'duvida'     => 'Dúvidas em aberto levantadas pela equipe',
        'risco'      => 'Riscos identificados pela equipe',
    ];

    $txt = "## Contextos adicionais da equipe\n\n";
    foreach ($labels as $tipo => $label) {
        $lista = array_values($por_tipo[$tipo] ?? []);
        if (empty($lista)) continue;
        $txt .= "### $label\n";
        foreach ($lista as $n) {
            $txt .= "- [{$n['usuario']}] {$n['texto']}\n";
        }
        $txt .= "\n";
    }
    return $txt;
}

function notas_por_tipo(array $notas): array
{
    $agrupadas = [];
    foreach (TIPOS_NOTA as $tipo => $_) {
        $agrupadas[$tipo] = array_filter($notas, fn($n) => ($n['tipo'] ?? '') === $tipo);
    }
    return $agrupadas;
}
