<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once "php/conexao.php";

function voltar_biblioteca($erro = '') {
    $destino = "wavify_my_library.php";
    if ($erro !== '') {
        $destino .= "?erro=" . urlencode($erro);
    }
    header("Location: " . $destino);
    exit;
}

$link = trim($_POST['youtube_link'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$artista = trim($_POST['artista'] ?? '');
$usuario_id = (int) $_SESSION['usuario_id'];

if ($link === '' || $titulo === '' || $artista === '') {
    voltar_biblioteca("Preencha o link, o t?tulo e o artista.");
}

$youtube_id = '';
$url = parse_url($link);

if (!empty($url['query'])) {
    parse_str($url['query'], $params);
    $youtube_id = $params['v'] ?? '';
}

if ($youtube_id === '' && !empty($url['host']) && strpos($url['host'], 'youtu.be') !== false) {
    $youtube_id = trim($url['path'] ?? '', '/');
}

if ($youtube_id === '' || !preg_match('/^[a-zA-Z0-9_-]{6,20}$/', $youtube_id)) {
    voltar_biblioteca("Link do YouTube inv?lido.");
}

$thumbnail = "https://i.ytimg.com/vi/$youtube_id/hqdefault.jpg";

$sql = "
INSERT INTO musicas (usuario_id, youtube_id, titulo, artista, thumbnail)
VALUES (?, ?, ?, ?, ?)
";
$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "issss", $usuario_id, $youtube_id, $titulo, $artista, $thumbnail);

if (!mysqli_stmt_execute($stmt)) {
    voltar_biblioteca("Não foi possível adicionar a música.");
}

header("Location: wavify_my_library.php");
exit;
?>
