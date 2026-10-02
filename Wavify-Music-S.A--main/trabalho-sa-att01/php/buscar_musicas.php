<?php
require_once 'app.php';
ensure_auth();

$usuario_id = (int) $_SESSION['usuario_id'];
$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode([]);
    exit;
}

global $conexao;
$search = '%' . $query . '%';
$stmt = mysqli_prepare($conexao, "
    SELECT * FROM musicas 
    WHERE usuario_id = ? 
    AND (titulo LIKE ? OR artista LIKE ? OR album LIKE ?) 
    ORDER BY titulo ASC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "isss", $usuario_id, $search, $search, $search);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tracks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tracks[] = [
        'id' => (int) $row['id'],
        'youtube_id' => $row['youtube_id'] ?? '',
        'audio_url' => $row['audio_url'] ?? '',
        'titulo' => $row['titulo'] ?? '',
        'artista' => $row['artista'] ?? '',
        'thumbnail' => $row['thumbnail'] ?? '',
        'genero' => $row['genero'] ?? '',
        'album' => $row['album'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($tracks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
