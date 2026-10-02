<?php
require_once 'app.php';
ensure_auth();

$url = trim($_GET['url'] ?? '');
if ($url === '') {
    echo json_encode(['erro' => 'URL vazia']);
    exit;
}

$youtube_id = extract_youtube_id($url);
if ($youtube_id === '') {
    echo json_encode(['erro' => 'ID do YouTube não identificado']);
    exit;
}

$metadata = fetch_youtube_metadata($youtube_id);
$metadata['youtube_id'] = $youtube_id;

header('Content-Type: application/json');
echo json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
