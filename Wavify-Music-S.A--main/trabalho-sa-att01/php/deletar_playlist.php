<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;

if ($playlist_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID da playlist inválido.']);
    exit;
}

global $conexao;

// Verify ownership and get cover info
$stmt_check = mysqli_prepare($conexao, 'SELECT id, capa FROM playlists WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_check, 'ii', $playlist_id, $usuario_id);
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
$playlist = mysqli_fetch_assoc($result);

if (!$playlist) {
    echo json_encode(['sucesso' => false, 'erro' => 'Playlist não encontrada ou sem permissão para excluí-la.']);
    exit;
}

mysqli_begin_transaction($conexao);

try {
    // 1. Delete associated track relationships
    $stmt_assoc = mysqli_prepare($conexao, 'DELETE FROM playlist_musicas WHERE playlist_id = ?');
    mysqli_stmt_bind_param($stmt_assoc, 'i', $playlist_id);
    mysqli_stmt_execute($stmt_assoc);

    // 2. Delete the playlist itself
    $stmt_del = mysqli_prepare($conexao, 'DELETE FROM playlists WHERE id = ? AND usuario_id = ?');
    mysqli_stmt_bind_param($stmt_del, 'ii', $playlist_id, $usuario_id);
    mysqli_stmt_execute($stmt_del);

    // 3. Delete physical cover image if uploaded local file
    if (!empty($playlist['capa']) && strpos($playlist['capa'], 'uploads/') !== false) {
        $caminho_arquivo = dirname(__DIR__) . '/' . ltrim($playlist['capa'], '/');
        if (file_exists($caminho_arquivo) && is_file($caminho_arquivo)) {
            @unlink($caminho_arquivo);
        }
    }

    mysqli_commit($conexao);
    echo json_encode(['sucesso' => true, 'id' => $playlist_id]);
} catch (Exception $e) {
    mysqli_rollback($conexao);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao excluir a playlist: ' . $e->getMessage()]);
}
?>
