<?php
$conexao = mysqli_connect(
    "localhost",
    "root",
    "",
    "musica"
);

if (!$conexao) {
    die("Erro na conex?o com o banco de dados.");
}

mysqli_set_charset($conexao, "utf8mb4");

mysqli_query($conexao, "
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

mysqli_query($conexao, "ALTER TABLE usuarios MODIFY senha VARCHAR(255) NOT NULL");
?>
