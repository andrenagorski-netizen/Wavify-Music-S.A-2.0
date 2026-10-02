<?php
session_start();

include("conexao.php");

function voltar_cadastro($erro) {
    header("Location: ../wavify_signup.php?erro=" . urlencode($erro));
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha_digitada = $_POST['senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';

if ($usuario === '' || $email === '' || $senha_digitada === '' || $confirma_senha === '') {
    voltar_cadastro("Preencha todos os campos.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltar_cadastro("Digite um email v?lido.");
}

if ($senha_digitada !== $confirma_senha) {
    voltar_cadastro("As senhas não coincidem.");
}

$verifica = mysqli_prepare($conexao, "SELECT id FROM usuarios WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($verifica, "s", $email);
mysqli_stmt_execute($verifica);
$resultado = mysqli_stmt_get_result($verifica);

if (mysqli_fetch_assoc($resultado)) {
    voltar_cadastro("Este email j? est? cadastrado.");
}

$senha = password_hash($senha_digitada, PASSWORD_DEFAULT);
$sql = "INSERT INTO usuarios (usuario, email, senha) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "sss", $usuario, $email, $senha);

if (!mysqli_stmt_execute($stmt)) {
    voltar_cadastro("Não foi possível criar a conta. Tente novamente.");
}

$_SESSION['usuario_id'] = mysqli_insert_id($conexao);
$_SESSION['usuario_nome'] = $usuario;
$_SESSION['usuario_email'] = $email;

header("Location: ../wavify_home.php");
exit;
?>
