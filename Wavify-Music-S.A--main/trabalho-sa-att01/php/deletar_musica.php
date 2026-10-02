<?php
require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

$usuario_id = (int)$_SESSION['usuario_id'];
$musica_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($musica_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID da música inválido.']);
    exit;
}

global $conexao;

// Verificar se a música pertence ao usuário logado
$stmt = mysqli_prepare($conexao, 'SELECT id, audio_url, origem FROM musicas WHERE id = ? AND usuario_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ii', $musica_id, $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$musica = mysqli_fetch_assoc($result);

if (!$musica) {
    echo json_encode(['sucesso' => false, 'erro' => 'Música não encontrada ou você não tem permissão para apagá-la.']);
    exit;
}

// Iniciar uma transação para garantir integridade
mysqli_begin_transaction($conexao);

try {
    // 1. Deletar referências na tabela playlist_musicas
    $stmt_playlist = mysqli_prepare($conexao, 'DELETE FROM playlist_musicas WHERE musica_id = ?');
    mysqli_stmt_bind_param($stmt_playlist, 'i', $musica_id);
    mysqli_stmt_execute($stmt_playlist);

    // 2. Deletar a música da tabela musicas
    $stmt_musica = mysqli_prepare($conexao, 'DELETE FROM musicas WHERE id = ? AND usuario_id = ?');
    mysqli_stmt_bind_param($stmt_musica, 'ii', $musica_id, $usuario_id);
    mysqli_stmt_execute($stmt_musica);

    // 3. Se for de upload, tentar remover o arquivo físico do disco
    if ($musica['origem'] === 'upload' && !empty($musica['audio_url'])) {
        // Obter caminho absoluto do arquivo
        // audio_url costuma ser algo como "uploads/audio/track_..."
        $caminho_arquivo = dirname(__DIR__) . '/' . ltrim($musica['audio_url'], '/');
        if (file_exists($caminho_arquivo) && is_file($caminho_arquivo)) {
            @unlink($caminho_arquivo);
        }
    }

    mysqli_commit($conexao);
    echo json_encode(['sucesso' => true, 'id' => $musica_id]);
} catch (Exception $e) {
    mysqli_rollback($conexao);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao deletar do banco de dados: ' . $e->getMessage()]);
}
?>
