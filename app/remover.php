<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/github.php';
require_once __DIR__ . '/lib/layout.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');

$path  = trim($_POST['path'] ?? '');
$slug  = trim($_POST['slug'] ?? '');

if (!$path || !$slug) redirect('/');

$arquivo = basename($path);
$ok      = gh_delete_file($path, "remove: $arquivo de $slug");

if ($ok) {
    redirect("/assunto.php?slug=$slug");
} else {
    layout_head('Erro');
    echo '<div class="container"><div class="alert alert-danger">Erro ao remover arquivo do GitHub.</div>';
    echo "<a href=\"/assunto.php?slug=" . urlencode($slug) . "\" class=\"btn btn-outline\">← Voltar</a></div>";
    layout_foot();
}
