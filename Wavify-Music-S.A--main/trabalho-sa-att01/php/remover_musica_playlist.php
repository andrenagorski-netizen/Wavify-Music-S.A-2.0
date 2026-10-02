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

// Delete mapping
$stmt_del = mysqli_prepare($conexao, 'DELETE FROM playlist_musicas WHERE playlist_id = ? AND musica_id = ?');
mysqli_stmt_bind_param($stmt_del, 'ii', $playlist_id, $musica_id);

if (mysqli_stmt_execute($stmt_del)) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao remover música da playlist.']);
}
?>
