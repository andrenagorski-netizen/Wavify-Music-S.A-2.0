<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

if ($playlist_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID da playlist inválido.']);
    exit;
}

if ($nome === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'O nome da playlist é obrigatório.']);
    exit;
}

global $conexao;

// Verify ownership
$stmt_check = mysqli_prepare($conexao, 'SELECT id, capa FROM playlists WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_check, 'ii', $playlist_id, $usuario_id);
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
$playlist = mysqli_fetch_assoc($result);

if (!$playlist) {
    echo json_encode(['sucesso' => false, 'erro' => 'Playlist não encontrada ou sem permissão.']);
    exit;
}

$capa_path = $playlist['capa'];

// Process cover art image upload if provided
if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['capa']['tmp_name'];
    $fileName = $_FILES['capa']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExtension, $allowedExtensions)) {
        $uploadFileDir = upload_dir('covers');
        $newFileName = 'cover_playlist_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $dest_path = $uploadFileDir . '/' . $newFileName;
        
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            // Delete old file if it was a local upload
            if (strpos($playlist['capa'], 'uploads/') !== false) {
                $caminho_antigo = dirname(__DIR__) . '/' . ltrim($playlist['capa'], '/');
                if (file_exists($caminho_antigo) && is_file($caminho_antigo)) {
                    @unlink($caminho_antigo);
                }
            }
            $capa_path = public_upload_path($dest_path);
        }
    }
}

$stmt_update = mysqli_prepare($conexao, 'UPDATE playlists SET nome = ?, descricao = ?, capa = ? WHERE id = ? AND usuario_id = ?');
mysqli_stmt_bind_param($stmt_update, 'sssii', $nome, $descricao, $capa_path, $playlist_id, $usuario_id);

if (mysqli_stmt_execute($stmt_update)) {
    echo json_encode([
        'sucesso' => true,
        'id' => $playlist_id,
        'nome' => $nome,
        'descricao' => $descricao,
        'capa' => $capa_path
    ]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar alterações no banco de dados.']);
}
?>
