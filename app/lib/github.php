<?php

function gh_request(string $method, string $endpoint, array $data = null): array
{
    $url = "https://api.github.com/repos/" . GITHUB_OWNER . "/" . GITHUB_REPO . $endpoint;

    $headers = [
        "Authorization: Bearer " . GITHUB_TOKEN,
        "Accept: application/vnd.github+json",
        "X-GitHub-Api-Version: 2022-11-28",
        "User-Agent: Unify-App",
        "Content-Type: application/json",
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => json_decode($body, true)];
}

function gh_list_dir(string $path): array
{
    $r = gh_request('GET', "/contents/" . GITHUB_DATA_PATH . "/$path");
    if ($r['status'] !== 200 || !is_array($r['body'])) return [];
    return $r['body'];
}

function gh_get_file(string $path): ?array
{
    $r = gh_request('GET', "/contents/" . GITHUB_DATA_PATH . "/$path");
    if ($r['status'] !== 200) return null;
    return $r['body'];
}

function gh_save_file(string $path, string $content, string $message): bool
{
    $data = [
        'message' => $message,
        'content' => base64_encode($content),
        'branch'  => GITHUB_BRANCH,
    ];

    // verifica se já existe para pegar o SHA
    $existing = gh_get_file($path);
    if ($existing && isset($existing['sha'])) {
        $data['sha'] = $existing['sha'];
    }

    $r = gh_request('PUT', "/contents/" . GITHUB_DATA_PATH . "/$path", $data);
    return in_array($r['status'], [200, 201]);
}

function gh_delete_file(string $path, string $message): bool
{
    $existing = gh_get_file($path);
    if (!$existing || !isset($existing['sha'])) return false;

    $r = gh_request('DELETE', "/contents/" . GITHUB_DATA_PATH . "/$path", [
        'message' => $message,
        'sha'     => $existing['sha'],
        'branch'  => GITHUB_BRANCH,
    ]);
    return $r['status'] === 200;
}

function gh_get_content(string $path): ?string
{
    $file = gh_get_file($path);
    if (!$file || !isset($file['content'])) return null;
    return base64_decode(str_replace("\n", '', $file['content']));
}

function gh_list_assuntos(): array
{
    $items = gh_list_dir('assuntos');
    return array_filter($items, fn($i) => $i['type'] === 'dir');
}

function gh_list_uploads(string $slug): array
{
    $users = gh_list_dir("assuntos/$slug/uploads");
    $result = [];
    foreach ($users as $u) {
        if ($u['type'] !== 'dir') continue;
        $files = gh_list_dir("assuntos/$slug/uploads/{$u['name']}");
        foreach ($files as $f) {
            if ($f['type'] === 'file' && str_ends_with($f['name'], '.md')) {
                $result[] = [
                    'usuario'  => $u['name'],
                    'arquivo'  => $f['name'],
                    'path'     => "assuntos/$slug/uploads/{$u['name']}/{$f['name']}",
                    'html_url' => $f['html_url'] ?? '',
                ];
            }
        }
    }
    return $result;
}
