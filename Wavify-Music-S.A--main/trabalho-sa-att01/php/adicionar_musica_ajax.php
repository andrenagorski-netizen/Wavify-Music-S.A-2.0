<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$link = isset($_POST['youtube_link']) ? trim($_POST['youtube_link']) : '';
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$artista = isset($_POST['artista']) ? trim($_POST['artista']) : '';
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;

if ($link === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Link do YouTube é obrigatório.']);
    exit;
}

if ($playlist_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Playlist inválida.']);
    exit;
}

$youtube_id = extract_youtube_id($link);

if ($youtube_id === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Link do YouTube inválido ou não suportado.']);
    exit;
}

// Fetch metadata from YouTube if fields are empty
if ($titulo === '' || $artista === '') {
    $metadata = fetch_youtube_metadata($youtube_id);
    if ($titulo === '') {
        $titulo = $metadata['titulo'] ?: 'Música do YouTube';
    }
    if ($artista === '') {
        $artista = $metadata['artista'] ?: 'Artista do YouTube';
    }
}

$thumbnail = "https://i.ytimg.com/vi/$youtube_id/hqdefault.jpg";

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

mysqli_begin_transaction($conexao);

try {
    // 1. Insert into musicas table
    $stmt_m = mysqli_prepare($conexao, "
        INSERT INTO musicas (usuario_id, youtube_id, titulo, artista, thumbnail, origem)
        VALUES (?, ?, ?, ?, ?, 'youtube')
    ");
    mysqli_stmt_bind_param($stmt_m, "issss", $usuario_id, $youtube_id, $titulo, $artista, $thumbnail);
    mysqli_stmt_execute($stmt_m);
    $musica_id = mysqli_insert_id($conexao);

    // 2. Associate with the playlist
    $stmt_assoc = mysqli_prepare($conexao, 'INSERT INTO playlist_musicas (playlist_id, musica_id) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt_assoc, 'ii', $playlist_id, $musica_id);
    mysqli_stmt_execute($stmt_assoc);

    mysqli_commit($conexao);
    echo json_encode([
        'sucesso' => true,
        'musica' => [
            'id' => $musica_id,
            'youtube_id' => $youtube_id,
            'titulo' => $titulo,
            'artista' => $artista,
            'thumbnail' => $thumbnail
        ]
    ]);
} catch (Exception $e) {
    mysqli_rollback($conexao);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar música na playlist: ' . $e->getMessage()]);
}
?>
