<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$playlist_id = isset($_GET['playlist_id']) ? (int)$_GET['playlist_id'] : 0;

if ($playlist_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID da playlist inválido.']);
    exit;
}

global $conexao;

// Verify playlist ownership
$stmt_check = mysqli_prepare($conexao, 'SELECT id FROM playlists WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_check, 'ii', $playlist_id, $usuario_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
if (mysqli_num_rows($res_check) === 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Playlist não encontrada ou sem permissão.']);
    exit;
}

$tracks = playlist_tracks($playlist_id, $usuario_id);

echo json_encode([
    'sucesso' => true,
    'musicas' => array_map(function ($musica) {
        return [
            'id' => (int) $musica['id'],
            'youtube_id' => $musica['youtube_id'] ?? '',
            'audio_url' => $musica['audio_url'] ?? '',
            'titulo' => $musica['titulo'] ?? 'Sem título',
            'artista' => $musica['artista'] ?? 'Artista desconhecido',
            'thumbnail' => $musica['thumbnail'] ?? '',
            'genero' => $musica['genero'] ?? '',
            'album' => $musica['album'] ?? '',
        ];
    }, $tracks)
]);
?>
