<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) exit('Slug inválido.');

$md = gh_get_content("assuntos/$slug/consolidado/resumo-final.md");
if (!$md) exit('Arquivo não encontrado.');

$nome_arq = "resumo-" . $slug . "-" . date('Ymd') . ".md";

header('Content-Type: text/plain; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$nome_arq\"");
header('Content-Length: ' . strlen($md));

echo $md;
