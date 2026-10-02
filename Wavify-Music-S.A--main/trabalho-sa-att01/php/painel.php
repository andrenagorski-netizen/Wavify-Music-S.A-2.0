<?php

session_start();

if(!isset($_SESSION['usuario_id'])){

    header("Location: ../index.php");

    exit;
}
?>

<h1>
Bem-vindo
<?php echo $_SESSION['usuario_nome']; ?>
</h1>

<a href="logout.php">
Sair
</a>