<?php

// Paleta de cores por usuário (slug → [bg, text])
const USUARIO_CORES = [
    'fabio'  => ['#dbeafe', '#1d4ed8'],
    'arthur' => ['#dcfce7', '#15803d'],
    'daniel' => ['#f3e8ff', '#7e22ce'],
    'renato' => ['#ffedd5', '#c2410c'],
    'carlos' => ['#fce7f3', '#be185d'],
    'joao'   => ['#e0f2fe', '#0369a1'],
];

function usuario_pill(string $nome): string
{
    $slug = mb_strtolower(trim($nome));
    $cores = USUARIO_CORES[$slug] ?? ['#f1f5f9', '#475569'];
    return sprintf(
        '<span style="display:inline-flex;align-items:center;background:%s;color:%s;'
        . 'border-radius:20px;padding:.05rem .55rem;font-size:.75rem;font-weight:600;'
        . 'white-space:nowrap;vertical-align:middle;margin:0 .15rem;letter-spacing:.01em;">%s</span>',
        $cores[0], $cores[1], htmlspecialchars(ucfirst($nome))
    );
}

function highlight_usuarios(string $html): string
{
    // Detecta [nome] — formato gerado pelo prompt da IA para atribuição
    $html = preg_replace_callback('/\[([a-zA-ZÀ-ú]{2,20})\]/', function ($m) {
        return usuario_pill($m[1]);
    }, $html);

    // Detecta "Usuário: nome" ou "Usuario nome:" no início de linha
    $html = preg_replace_callback('/\b(Usuário|Usuario):\s*([a-zA-ZÀ-ú]{2,20})\b/', function ($m) {
        return $m[1] . ': ' . usuario_pill($m[2]);
    }, $html);

    return $html;
}

function md_to_html(string $md): string
{
    // code blocks (fenced)
    $md = preg_replace_callback('/```(\w*)\n([\s\S]*?)```/m', function ($m) {
        $lang = htmlspecialchars($m[1]);
        $code = htmlspecialchars($m[2]);
        return "<pre class=\"code-block\"><code class=\"language-$lang\">$code</code></pre>";
    }, $md);

    // inline code
    $md = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $md);

    $lines  = explode("\n", $md);
    $html   = '';
    $in_ul  = false;
    $in_ol  = false;

    foreach ($lines as $line) {
        // headings
        if (preg_match('/^#{1,6}\s/', $line)) {
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }
            $level = strlen(strstr($line, ' ', true));
            $text  = trim(substr($line, $level + 1));
            $text  = inline_fmt($text);
            $html .= "<h$level>$text</h$level>\n";
            continue;
        }

        // unordered list
        if (preg_match('/^[\-\*]\s+(.+)/', $line, $m)) {
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }
            if (!$in_ul) { $html .= '<ul>'; $in_ul = true; }
            $html .= '<li>' . inline_fmt($m[1]) . '</li>';
            continue;
        }

        // ordered list
        if (preg_match('/^\d+\.\s+(.+)/', $line, $m)) {
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if (!$in_ol) { $html .= '<ol>'; $in_ol = true; }
            $html .= '<li>' . inline_fmt($m[1]) . '</li>';
            continue;
        }

        // horizontal rule
        if (preg_match('/^-{3,}$/', trim($line))) {
            if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
            if ($in_ol) { $html .= '</ol>'; $in_ol = false; }
            $html .= '<hr>';
            continue;
        }

        // close lists on blank line or regular paragraph
        if ($in_ul) { $html .= '</ul>'; $in_ul = false; }
        if ($in_ol) { $html .= '</ol>'; $in_ol = false; }

        if (trim($line) === '') {
            $html .= '';
            continue;
        }

        $html .= '<p>' . inline_fmt($line) . '</p>' . "\n";
    }

    if ($in_ul) $html .= '</ul>';
    if ($in_ol) $html .= '</ol>';

    return $html;
}

function inline_fmt(string $text): string
{
    // bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    // italic
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    // inline code (já processado antes, mas por segurança)
    $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text);
    return $text;
}

// Divide o MD em seções por h2 e retorna array com título e conteúdo
function parse_sections(string $md): array
{
    $sections = [];
    $parts    = preg_split('/^## /m', $md, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $i => $part) {
        if ($i === 0 && !preg_match('/^#\s/', ltrim($part))) {
            // conteúdo antes do primeiro h2 (pode ter h1)
            $sections[] = ['titulo' => null, 'conteudo' => trim($part), 'tipo' => 'intro'];
            continue;
        }
        $lines   = explode("\n", $part, 2);
        $titulo  = trim($lines[0] ?? '');
        $conteudo = trim($lines[1] ?? '');
        $tipo    = secao_tipo($titulo);
        $sections[] = compact('titulo', 'conteudo', 'tipo');
    }

    return $sections;
}

function secao_tipo(string $titulo): string
{
    $mapa = [
        'resumo executivo'    => 'executivo',
        'contexto'            => 'contexto',
        'principais pontos'   => 'pontos',
        'pontos de consenso'  => 'consenso',
        'pontos divergentes'  => 'divergentes',
        'riscos'              => 'riscos',
        'custos'              => 'custos',
        'dúvidas'             => 'duvidas',
        'recomendação'        => 'recomendacao',
        'próximos passos'     => 'proximos',
        'fontes'              => 'fontes',
    ];

    $lower = mb_strtolower($titulo);
    foreach ($mapa as $chave => $tipo) {
        if (str_contains($lower, $chave)) return $tipo;
    }
    return 'generico';
}
