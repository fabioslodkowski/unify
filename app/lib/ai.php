<?php

require_once __DIR__ . '/notas.php';

function ai_consolidar(string $assunto, array $arquivos, array $ctx = []): string
{
    $provider = AI_PROVIDER;

    $max_por_arquivo = 3000;
    $max_total       = 18000;

    $contexto  = "Assunto: **$assunto**\n\n";
    if (!empty($ctx['global']))   $contexto .= global_para_prompt($ctx['global']) . "\n\n";
    if (!empty($ctx['problema'])) $contexto .= "**Problema a resolver:** {$ctx['problema']}\n\n";
    if (!empty($ctx['objetivo'])) $contexto .= "**Objetivo:** {$ctx['objetivo']}\n\n";
    if (!empty($ctx['notas']))    $contexto .= notas_para_prompt($ctx['notas']) . "\n\n";
    $total_chars = 0;
    foreach ($arquivos as $a) {
        $conteudo = $a['conteudo'];
        if (strlen($conteudo) > $max_por_arquivo) {
            $conteudo = substr($conteudo, 0, $max_por_arquivo) . "\n\n[... conteúdo truncado ...]";
        }
        $bloco = "---\n### Arquivo: {$a['arquivo']} (Usuário: {$a['usuario']})\n\n{$conteudo}\n\n";
        if ($total_chars + strlen($bloco) > $max_total) break;
        $contexto   .= $bloco;
        $total_chars += strlen($bloco);
    }

    $prompt = <<<PROMPT
Você receberá vários arquivos Markdown sobre o mesmo assunto enviados por diferentes pessoas.

Sua tarefa é consolidar todo o conteúdo em um único Markdown final, bem estruturado e profissional.

Regras:
- Não descarte informações relevantes
- Agrupe ideias semelhantes
- Identifique pontos de consenso e divergência
- Liste riscos, dúvidas e próximos passos
- Gere uma recomendação clara
- Quando possível, cite de qual arquivo/usuário veio cada informação
- Use emojis nos títulos das seções para facilitar leitura visual
- Escreva em português do Brasil

Estrutura obrigatória de saída:

# Resumo Consolidado — {$assunto}

## 🎯 Resumo Executivo
## 📋 Contexto
## 💡 Principais Pontos Levantados
## ✅ Pontos de Consenso
## ⚡ Pontos Divergentes
## ⚠️ Riscos Identificados
## 💰 Custos e Impactos
## ❓ Dúvidas em Aberto
## 🏆 Recomendação Inicial
## 🚀 Próximos Passos
## 📚 Fontes Utilizadas

Conteúdo dos arquivos:

{$contexto}
PROMPT;

    if ($provider === 'claude') {
        return ai_claude($prompt);
    } else {
        return ai_openai($prompt);
    }
}

function ai_consolidar_incremental(string $assunto, string $consolidado_anterior, array $novos, array $notas_novas = [], array $ctx = []): string
{
    $provider = AI_PROVIDER;

    $max_por_arquivo = 4000;
    $max_novos       = 12000;

    $novos_ctx   = '';
    $total_chars = 0;
    foreach ($novos as $a) {
        $conteudo = $a['conteudo'];
        if (strlen($conteudo) > $max_por_arquivo) {
            $conteudo = substr($conteudo, 0, $max_por_arquivo) . "\n\n[... conteúdo truncado ...]";
        }
        $bloco = "---\n### Arquivo: {$a['arquivo']} (Usuário: {$a['usuario']})\n\n{$conteudo}\n\n";
        if ($total_chars + strlen($bloco) > $max_novos) break;
        $novos_ctx   .= $bloco;
        $total_chars += strlen($bloco);
    }

    // Trunca o consolidado anterior se for muito grande
    $anterior_truncado = strlen($consolidado_anterior) > 6000
        ? substr($consolidado_anterior, 0, 6000) . "\n\n[... resumo anterior truncado ...]"
        : $consolidado_anterior;

    $ctx_txt = '';
    if (!empty($ctx['problema'])) $ctx_txt .= "**Problema a resolver:** {$ctx['problema']}\n";
    if (!empty($ctx['objetivo'])) $ctx_txt .= "**Objetivo:** {$ctx['objetivo']}\n";
    if (!empty($ctx['global']))   $ctx_txt .= global_para_prompt($ctx['global']) . "\n";
    if ($ctx_txt) $ctx_txt = "\n$ctx_txt\n";

    // Notas novas formatadas separadamente
    $notas_ctx = !empty($notas_novas)
        ? "\n## Novas notas da equipe a incorporar:\n\n" . notas_para_prompt($notas_novas)
        : '';

    $descricao = [];
    if (!empty($novos))       $descricao[] = count($novos) . ' novo(s) arquivo(s)';
    if (!empty($notas_novas)) $descricao[] = count($notas_novas) . ' nova(s) nota(s)';
    $desc = implode(' e ', $descricao) ?: 'novas contribuições';

    $prompt = <<<PROMPT
Você tem um resumo consolidado atual e novas contribuições para incorporar ({$desc}).

Regras:
- Mantenha toda a estrutura e informações do resumo atual
- Incorpore apenas o que é novo — não repita o que já está no consolidado
- Identifique se as novidades confirmam, divergem ou complementam o consolidado
- Atualize as seções afetadas (consenso, divergências, riscos, próximos passos, etc.)
- Notas do tipo "Decisão" são fatos consolidados — incorpore como tal
- Notas do tipo "Restrição" devem aparecer nas restrições/riscos
- Adicione novos arquivos na seção de Fontes Utilizadas
- Use emojis nos títulos das seções
- Escreva em português do Brasil

Assunto: **{$assunto}**{$ctx_txt}

## Resumo consolidado atual (base):

{$anterior_truncado}

## Novos arquivos a incorporar:

{$novos_ctx}{$notas_ctx}

Gere o resumo consolidado atualizado com a mesma estrutura de seções.
PROMPT;

    if ($provider === 'claude') {
        return ai_claude($prompt);
    } else {
        return ai_openai($prompt);
    }
}

function ai_claude(string $prompt): string
{
    $payload = [
        'model'      => CLAUDE_MODEL,
        'max_tokens' => 4096,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "x-api-key: " . CLAUDE_API_KEY,
            "anthropic-version: 2023-06-01",
            "content-type: application/json",
        ],
    ]);

    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        $err = json_decode($body, true);
        throw new Exception("Claude API erro $status: " . ($err['error']['message'] ?? $body));
    }

    $r = json_decode($body, true);
    return $r['content'][0]['text'] ?? '';
}

function ai_openai(string $prompt): string
{
    $payload = [
        'model'    => OPENAI_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . OPENAI_API_KEY,
            "Content-Type: application/json",
        ],
    ]);

    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        $err = json_decode($body, true);
        throw new Exception("OpenAI API erro $status: " . ($err['error']['message'] ?? $body));
    }

    $r = json_decode($body, true);
    return $r['choices'][0]['message']['content'] ?? '';
}
