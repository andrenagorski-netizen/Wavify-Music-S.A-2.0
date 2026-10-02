<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Usuario nao logado']);
    exit;
}

require_once "conexao.php";

$usuario_id = (int) $_SESSION['usuario_id'];
$musica_id = (int) ($_POST['musica_id'] ?? 0);

if ($musica_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Musica invalida']);
    exit;
}

mysqli_query($conexao, "
CREATE TABLE IF NOT EXISTS reproducoes_recentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    musica_id INT NOT NULL,
    tocada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY usuario_musica (usuario_id, musica_id)
)
");

$verifica_sql = "
SELECT id
FROM musicas
WHERE id = '$musica_id'
AND usuario_id = '$usuario_id'
LIMIT 1
";

$verifica_resultado = mysqli_query($conexao, $verifica_sql);

if (!$verifica_resultado || mysqli_num_rows($verifica_resultado) === 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Musica nao pertence ao usuario']);
    exit;
}

$sql = "
INSERT INTO reproducoes_recentes (usuario_id, musica_id, tocada_em)
VALUES ('$usuario_id', '$musica_id', NOW())
ON DUPLICATE KEY UPDATE tocada_em = NOW()
";

if (mysqli_query($conexao, $sql)) {
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(500);
echo json_encode(['ok' => false, 'erro' => mysqli_error($conexao)]);
?>
