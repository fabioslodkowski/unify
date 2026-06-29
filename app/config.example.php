<?php
// ============================================================
//  Unify — Configurações (COPIE para config.php e preencha)
// ============================================================

// GitHub
define('GITHUB_TOKEN',  'ghp_SEU_TOKEN_AQUI');
define('GITHUB_OWNER',  'fabioslodkowski');
define('GITHUB_REPO',   'unify');
define('GITHUB_BRANCH', 'main');
define('GITHUB_DATA_PATH', 'dados');

// Claude (Anthropic) — https://console.anthropic.com
define('CLAUDE_API_KEY', 'sk-ant-SEU_TOKEN_AQUI');
define('CLAUDE_MODEL',   'claude-sonnet-4-6');    // claude-opus-4-8 | claude-sonnet-4-6 | claude-haiku-4-5-20251001

// OpenAI — https://platform.openai.com
define('OPENAI_API_KEY', 'sk-SEU_TOKEN_AQUI');
define('OPENAI_MODEL',   'gpt-4o');               // gpt-4o | gpt-4o-mini | gpt-4-turbo

// Provedor ativo: 'claude' ou 'openai'
define('AI_PROVIDER', 'claude');

// Usuários pré-cadastrados (slug => nome exibido)
$USUARIOS = [
    'fabio'  => 'Fabio',
    'arthur' => 'Arthur',
    'daniel' => 'Daniel',
    'renato' => 'Renato',
];
