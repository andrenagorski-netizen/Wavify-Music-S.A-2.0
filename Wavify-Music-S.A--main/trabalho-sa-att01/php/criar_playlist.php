<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

if ($nome === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'O nome da playlist é obrigatório.']);
    exit;
}

$capa_path = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=300&auto=format&fit=crop';

// Process cover art image upload
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
            $capa_path = public_upload_path($dest_path);
        }
    }
}

global $conexao;

$musicas_selecionadas = isset($_POST['musicas']) && is_array($_POST['musicas']) ? $_POST['musicas'] : [];

mysqli_begin_transaction($conexao);

try {
    $stmt = mysqli_prepare($conexao, 'INSERT INTO playlists (usuario_id, nome, descricao, capa) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isss', $usuario_id, $nome, $descricao, $capa_path);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Erro ao criar a playlist no banco de dados.');
    }
    
    $playlist_id = mysqli_insert_id($conexao);
    
    if (!empty($musicas_selecionadas)) {
        // Verify ownership and insert association
        $stmt_verify = mysqli_prepare($conexao, 'SELECT id FROM musicas WHERE id = ? AND usuario_id = ? LIMIT 1');
        $stmt_assoc = mysqli_prepare($conexao, 'INSERT INTO playlist_musicas (playlist_id, musica_id) VALUES (?, ?)');
        
        foreach ($musicas_selecionadas as $m_id) {
            $m_id = (int)$m_id;
            if ($m_id <= 0) continue;
            
            mysqli_stmt_bind_param($stmt_verify, 'ii', $m_id, $usuario_id);
            mysqli_stmt_execute($stmt_verify);
            $res_verify = mysqli_stmt_get_result($stmt_verify);
            
            if (mysqli_num_rows($res_verify) > 0) {
                mysqli_stmt_bind_param($stmt_assoc, 'ii', $playlist_id, $m_id);
                mysqli_stmt_execute($stmt_assoc);
            }
        }
    }
    
    mysqli_commit($conexao);
    echo json_encode([
        'sucesso' => true, 
        'id' => $playlist_id, 
        'nome' => $nome, 
        'descricao' => $descricao, 
        'capa' => $capa_path
    ]);
} catch (Exception $e) {
    mysqli_rollback($conexao);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>
