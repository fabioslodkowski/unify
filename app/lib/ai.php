<?php

function ai_consolidar(string $assunto, array $arquivos): string
{
    $provider = AI_PROVIDER;

    $contexto = "Assunto: **$assunto**\n\n";
    foreach ($arquivos as $a) {
        $contexto .= "---\n### Arquivo: {$a['arquivo']} (Usuário: {$a['usuario']})\n\n{$a['conteudo']}\n\n";
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
