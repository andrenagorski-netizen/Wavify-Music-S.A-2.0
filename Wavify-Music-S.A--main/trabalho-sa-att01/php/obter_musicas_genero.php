<?php
require_once 'app.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];
$genero = trim($_GET['genero'] ?? '');

if ($genero === '') {
    echo json_encode([]);
    exit;
}

global $conexao;
$stmt = mysqli_prepare($conexao, "SELECT * FROM musicas WHERE usuario_id = ? AND genero = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "is", $usuario_id, $genero);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tracks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tracks[] = [
        'id' => (int) $row['id'],
        'youtube_id' => $row['youtube_id'] ?? '',
        'audio_url' => $row['audio_url'] ?? '',
        'titulo' => $row['titulo'] ?? 'Sem título',
        'artista' => $row['artista'] ?? 'Artista desconhecido',
        'thumbnail' => $row['thumbnail'] ?? '',
        'genero' => $row['genero'] ?? '',
        'album' => $row['album'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
