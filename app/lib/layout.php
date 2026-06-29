<?php
function layout_head(string $titulo = 'Unify'): void
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$titulo} — Unify</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav class="navbar">
  <a class="navbar-brand" href="/"><span>uni</span>fy</a>
  <span class="navbar-sep"></span>
  <a href="/novo-assunto.php" class="btn btn-primary btn-sm">+ Novo Assunto</a>
</nav>
HTML;
}

function layout_foot(): void
{
    echo '</body></html>';
}

function flash(string $tipo, string $msg): void
{
    echo "<div class=\"alert alert-{$tipo}\">{$msg}</div>";
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function slugify(string $texto): string
{
    $texto = mb_strtolower($texto);
    $mapa  = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c'];
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/[^a-z0-9\s\-]/', '', $texto);
    $texto = preg_replace('/[\s\-]+/', '-', trim($texto));
    return $texto;
}
