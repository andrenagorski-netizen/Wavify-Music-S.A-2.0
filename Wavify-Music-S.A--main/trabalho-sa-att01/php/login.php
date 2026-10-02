<?php
session_start();

include("conexao.php");

function voltar_login($erro) {
    header("Location: ../index.php?erro=" . urlencode($erro));
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    voltar_login("Preencha email e senha.");
}

$sql = "SELECT id, usuario, email, senha, foto FROM usuarios WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

if (!$usuario) {
    voltar_login("Usuário não encontrado.");
}

$senha_salva = $usuario['senha'];
$senha_correta = password_verify($senha, $senha_salva);

if (!$senha_correta && password_get_info($senha_salva)['algo'] === 0 && hash_equals($senha_salva, $senha)) {
    $senha_correta = true;
    $nova_senha = password_hash($senha, PASSWORD_DEFAULT);
    $update = mysqli_prepare($conexao, "UPDATE usuarios SET senha = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "si", $nova_senha, $usuario['id']);
    mysqli_stmt_execute($update);
}

if (!$senha_correta) {
    voltar_login("Senha incorreta.");
}

if (password_needs_rehash($senha_salva, PASSWORD_DEFAULT)) {
    $nova_senha = password_hash($senha, PASSWORD_DEFAULT);
    $update = mysqli_prepare($conexao, "UPDATE usuarios SET senha = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "si", $nova_senha, $usuario['id']);
    mysqli_stmt_execute($update);
}

$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['usuario'];
$_SESSION['usuario_email'] = $usuario['email'];
$_SESSION['wavify_user_img'] = $usuario['foto'];

header("Location: ../wavify_home.php");
exit;
?>
