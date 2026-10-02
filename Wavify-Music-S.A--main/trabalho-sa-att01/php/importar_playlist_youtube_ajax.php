<?php
// =====================================================================
// NOVA FUNCIONALIDADE: Importar Playlist do YouTube
// Arquivo: php/importar_playlist_youtube_ajax.php
//
// Como funciona:
//   1. Recebe um link de playlist do YouTube (ou YouTube Music)
//   2. Extrai o playlist_id do link (ex: PLxxxxxx)
//   3. Chama a YouTube Data API v3 para buscar todos os vídeos
//   4. Para cada vídeo:
//      a. Salva na tabela 'musicas' do usuário
//      b. Usa a miniatura do YouTube como capa
//   5. Cria uma nova entrada na tabela 'playlists'
//   6. Vincula todos os vídeos à playlist criada
//
// ⚠️ IMPORTANTE: Você precisa ter uma chave da YouTube Data API v3.
//    Substitua 'SUA_YOUTUBE_API_KEY_AQUI' pela sua chave real.
//    Obtenha em: https://console.cloud.google.com/
// =====================================================================

require_once __DIR__ . '/app.php';
ensure_auth();

header('Content-Type: application/json');

// ── Chave da API do YouTube Data v3 ──────────────────────────────────
// Substitua pelo seu valor real!
define('YOUTUBE_API_KEY', 'SUA_YOUTUBE_API_KEY_AQUI');

$usuario_id = (int) $_SESSION['usuario_id'];

// ── Parâmetros recebidos do formulário ───────────────────────────────
$link        = isset($_POST['playlist_link']) ? trim($_POST['playlist_link']) : '';
$nome_custom = isset($_POST['nome'])          ? trim($_POST['nome'])          : '';

// Validação básica do link
if ($link === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Link da playlist é obrigatório.']);
    exit;
}

// ── Extrair o playlist ID do link ────────────────────────────────────
$playlist_yt_id = extract_youtube_playlist_id($link);

if ($playlist_yt_id === '') {
    echo json_encode([
        'sucesso' => false,
        'erro'    => 'Link inválido. Use um link do tipo: youtube.com/playlist?list=PLxxxxx'
    ]);
    exit;
}

// ── Verificar se a API Key está configurada ──────────────────────────
if (YOUTUBE_API_KEY === 'SUA_YOUTUBE_API_KEY_AQUI') {
    echo json_encode([
        'sucesso' => false,
        'erro'    => 'API do YouTube não configurada. Abra php/importar_playlist_youtube_ajax.php e substitua SUA_YOUTUBE_API_KEY_AQUI pela sua chave.'
    ]);
    exit;
}

// ── Função auxiliar: buscar página de vídeos da playlist via API ─────
function fetch_playlist_page(string $playlist_id, string $page_token = ''): array {
    $url = 'https://www.googleapis.com/youtube/v3/playlistItems'
         . '?part=snippet'
         . '&maxResults=50'
         . '&playlistId=' . urlencode($playlist_id)
         . '&key=' . YOUTUBE_API_KEY;

    if ($page_token !== '') {
        $url .= '&pageToken=' . urlencode($page_token);
    }

    $json = @file_get_contents($url);
    if (!$json) return [];

    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// ── Buscar TODOS os vídeos da playlist (paginação automática) ────────
$all_videos  = [];
$page_token  = '';
$playlist_title = ''; // Será preenchido com o título da primeira resposta

do {
    $page = fetch_playlist_page($playlist_yt_id, $page_token);

    // Verifica erros da API
    if (!empty($page['error'])) {
        $api_msg = $page['error']['message'] ?? 'Erro desconhecido da API.';
        echo json_encode(['sucesso' => false, 'erro' => 'Erro da API do YouTube: ' . $api_msg]);
        exit;
    }

    if (empty($page['items'])) break;

    // Pega o título da playlist da primeira página
    if ($playlist_title === '' && !empty($page['items'][0]['snippet']['channelTitle'])) {
        // Tentar pegar o título real da playlist via API separada
        $pl_info_url = 'https://www.googleapis.com/youtube/v3/playlists'
                     . '?part=snippet&id=' . urlencode($playlist_yt_id)
                     . '&key=' . YOUTUBE_API_KEY;
        $pl_json = @file_get_contents($pl_info_url);
        if ($pl_json) {
            $pl_data = json_decode($pl_json, true);
            $playlist_title = $pl_data['items'][0]['snippet']['title'] ?? '';
        }
    }

    foreach ($page['items'] as $item) {
        $snippet   = $item['snippet'] ?? [];
        $video_id  = $snippet['resourceId']['videoId'] ?? '';
        $title     = $snippet['title']         ?? '';
        $channel   = $snippet['videoOwnerChannelTitle'] ?? $snippet['channelTitle'] ?? '';
        $thumb     = $snippet['thumbnails']['high']['url']
                  ?? $snippet['thumbnails']['medium']['url']
                  ?? $snippet['thumbnails']['default']['url']
                  ?? "https://i.ytimg.com/vi/{$video_id}/hqdefault.jpg";

        // Pula vídeos deletados/privados
        if ($video_id === '' || $title === 'Deleted video' || $title === 'Private video') {
            continue;
        }

        // Mesma limpeza de título que fetch_youtube_metadata usa
        $artist = $channel;
        $clean_title = $title;
        if (preg_match('/^(.*?)\s+[-–—|]\s+(.*)$/', $title, $m)) {
            $artist      = trim($m[1]);
            $clean_title = trim($m[2]);
        }
        $clean_title = preg_replace('/\s*[([][^)]*?(Official|Video|Audio|Lyric|Lirica|HD|4K|Clip|M\/V|Music|Videoclipe)[^(]*?[\])]/i', '', $clean_title);
        $clean_title = trim($clean_title);
        $artist = preg_replace('/\s+-\s+Topic$/i', '', $artist);

        $all_videos[] = [
            'youtube_id' => $video_id,
            'titulo'     => $clean_title ?: $title,
            'artista'    => $artist,
            'thumbnail'  => $thumb,
        ];
    }

    $page_token = $page['nextPageToken'] ?? '';

} while ($page_token !== '');

// Verifica se encontrou vídeos
if (empty($all_videos)) {
    echo json_encode([
        'sucesso' => false,
        'erro'    => 'Nenhum vídeo encontrado nessa playlist. Verifique se ela é pública.'
    ]);
    exit;
}

// ── Nome final da playlist (usa o customizado se fornecido) ──────────
$nome_final = $nome_custom !== '' ? $nome_custom : ($playlist_title ?: 'Playlist Importada');

// ── Usar a capa do primeiro vídeo como capa da playlist ──────────────
$capa_playlist = $all_videos[0]['thumbnail'];

global $conexao;
mysqli_begin_transaction($conexao);

try {
    // 1. Criar a playlist no banco
    $stmt_pl = mysqli_prepare($conexao,
        'INSERT INTO playlists (usuario_id, nome, descricao, capa) VALUES (?, ?, ?, ?)'
    );
    $descricao_pl = "Importada do YouTube · " . count($all_videos) . " músicas";
    mysqli_stmt_bind_param($stmt_pl, 'isss', $usuario_id, $nome_final, $descricao_pl, $capa_playlist);
    mysqli_stmt_execute($stmt_pl);
    $nova_playlist_id = mysqli_insert_id($conexao);

    $musicas_salvas  = 0;
    $musicas_puladas = 0; // duplicatas

    // 2. Inserir cada vídeo na biblioteca e vincular à playlist
    foreach ($all_videos as $video) {
        // Verifica se este youtube_id já existe na biblioteca do usuário
        $stmt_check = mysqli_prepare($conexao,
            'SELECT id FROM musicas WHERE usuario_id = ? AND youtube_id = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt_check, 'is', $usuario_id, $video['youtube_id']);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        $existing  = mysqli_fetch_assoc($res_check);

        if ($existing) {
            // Música já existe na biblioteca: apenas vincula à playlist nova
            $musica_id = (int) $existing['id'];
            $musicas_puladas++;
        } else {
            // Música nova: salva na tabela musicas
            $stmt_m = mysqli_prepare($conexao,
                "INSERT INTO musicas (usuario_id, youtube_id, titulo, artista, thumbnail, origem)
                 VALUES (?, ?, ?, ?, ?, 'youtube')"
            );
            mysqli_stmt_bind_param($stmt_m, 'issss',
                $usuario_id,
                $video['youtube_id'],
                $video['titulo'],
                $video['artista'],
                $video['thumbnail']
            );
            mysqli_stmt_execute($stmt_m);
            $musica_id = mysqli_insert_id($conexao);
            $musicas_salvas++;
        }

        // Vincula a música à nova playlist (ignora se já estiver vinculada)
        $stmt_assoc = mysqli_prepare($conexao,
            'INSERT IGNORE INTO playlist_musicas (playlist_id, musica_id) VALUES (?, ?)'
        );
        mysqli_stmt_bind_param($stmt_assoc, 'ii', $nova_playlist_id, $musica_id);
        mysqli_stmt_execute($stmt_assoc);
    }

    mysqli_commit($conexao);

    // Resposta de sucesso com estatísticas da importação
    echo json_encode([
        'sucesso'         => true,
        'playlist_id'     => $nova_playlist_id,
        'nome'            => $nome_final,
        'total'           => count($all_videos),
        'novas'           => $musicas_salvas,
        'ja_existiam'     => $musicas_puladas,
    ]);

} catch (Exception $e) {
    mysqli_rollback($conexao);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
}
?>
