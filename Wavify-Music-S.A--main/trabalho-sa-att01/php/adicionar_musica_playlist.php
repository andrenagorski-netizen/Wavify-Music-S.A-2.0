<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
$musica_id = isset($_POST['musica_id']) ? (int)$_POST['musica_id'] : 0;

if ($playlist_id <= 0 || $musica_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'IDs inválidos.']);
    exit;
}

global $conexao;

// Verify playlist ownership
$stmt_p = mysqli_prepare($conexao, 'SELECT id FROM playlists WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_p, 'ii', $playlist_id, $usuario_id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
if (mysqli_num_rows($res_p) === 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Playlist não encontrada ou sem permissão.']);
    exit;
}

// Verify music ownership
$stmt_m = mysqli_prepare($conexao, 'SELECT id FROM musicas WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_m, 'ii', $musica_id, $usuario_id);
mysqli_stmt_execute($stmt_m);
$res_m = mysqli_stmt_get_result($stmt_m);
if (mysqli_num_rows($res_m) === 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Música não encontrada na sua biblioteca.']);
    exit;
}

// Check if already in playlist
$stmt_check = mysqli_prepare($conexao, 'SELECT id FROM playlist_musicas WHERE playlist_id = ? AND musica_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_check, 'ii', $playlist_id, $musica_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
if (mysqli_num_rows($res_check) > 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Esta música já foi adicionada a esta playlist.']);
    exit;
}

// Insert relation
$stmt_add = mysqli_prepare($conexao, 'INSERT INTO playlist_musicas (playlist_id, musica_id) VALUES (?, ?)');
mysqli_stmt_bind_param($stmt_add, 'ii', $playlist_id, $musica_id);

if (mysqli_stmt_execute($stmt_add)) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao adicionar música à playlist.']);
}
?>
