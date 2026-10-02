<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$playlists = user_playlists($usuario_id);

echo json_encode(['sucesso' => true, 'playlists' => $playlists]);
?>
