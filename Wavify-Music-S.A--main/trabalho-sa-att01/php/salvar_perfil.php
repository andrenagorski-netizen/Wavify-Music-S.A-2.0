<?php
require_once 'app.php';
ensure_auth();

$usuario_id = (int) $_SESSION['usuario_id'];

$usuario = trim($_POST['usuario'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha_atual = $_POST['senha_atual'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

if ($usuario === '' || $email === '') {
    header("Location: ../wavify_profile.php?erro=" . urlencode("Nome e email não podem ser vazios."));
    exit;
}

global $conexao;

// Fetch current user record to verify password changes
$query = mysqli_prepare($conexao, "SELECT senha, foto FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($query, "i", $usuario_id);
mysqli_stmt_execute($query);
$res = mysqli_stmt_get_result($query);
$user_data = mysqli_fetch_assoc($res);
$senha_atual_hash = $user_data['senha'] ?? '';
$foto_atual = $user_data['foto'] ?? '';

// Check email uniqueness (excluding current user)
$check = mysqli_prepare($conexao, "SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
mysqli_stmt_bind_param($check, "si", $email, $usuario_id);
mysqli_stmt_execute($check);
$resultado_check = mysqli_stmt_get_result($check);
if (mysqli_fetch_assoc($resultado_check)) {
    header("Location: ../wavify_profile.php?erro=" . urlencode("Este email já está sendo utilizado por outra conta."));
    exit;
}

// Password update logic
$senha_query_part = "";
$senha_bind_param = "";
$nova_senha_hash = "";

if ($nova_senha !== '') {
    if (!password_verify($senha_atual, $senha_atual_hash)) {
        header("Location: ../wavify_profile.php?erro=" . urlencode("Senha atual incorreta."));
        exit;
    }
    if ($nova_senha !== $confirmar_senha) {
        header("Location: ../wavify_profile.php?erro=" . urlencode("As senhas novas não coincidem."));
        exit;
    }
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
}

// Profile picture upload logic
$foto_nova = $foto_atual;
if (isset($_FILES['foto_upload']) && $_FILES['foto_upload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['foto_upload']['tmp_name'];
    $fileName = $_FILES['foto_upload']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExtension, $allowedExtensions)) {
        $uploadFileDir = upload_dir('profiles');
        $newFileName = 'profile_' . $usuario_id . '_' . time() . '.' . $fileExtension;
        $dest_path = $uploadFileDir . '/' . $newFileName;
        
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $foto_nova = public_upload_path($dest_path);
        }
    }
}

// Perform update
if ($nova_senha !== '') {
    $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario = ?, email = ?, senha = ?, foto = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssssi", $usuario, $email, $nova_senha_hash, $foto_nova, $usuario_id);
} else {
    $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET usuario = ?, email = ?, foto = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $usuario, $email, $foto_nova, $usuario_id);
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['usuario_nome'] = $usuario;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['wavify_user_img'] = $foto_nova;
    header("Location: ../wavify_profile.php?sucesso=" . urlencode("Perfil atualizado com sucesso!"));
} else {
    header("Location: ../wavify_profile.php?erro=" . urlencode("Erro ao atualizar o perfil no banco de dados."));
}
exit;
