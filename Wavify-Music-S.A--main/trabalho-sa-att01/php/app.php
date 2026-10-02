<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexao.php';

function ensure_auth(): void {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php');
        exit;
    }
}

function current_user(): array {
    global $conexao;
    $id = (int) ($_SESSION['usuario_id'] ?? 0);
    if ($id <= 0) return [];

    $stmt = mysqli_prepare($conexao, 'SELECT id, usuario, email, foto FROM usuarios WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result) ?: [];

    if ($user) {
        $_SESSION['usuario_nome'] = $user['usuario'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['wavify_user_img'] = $user['foto'];
    }

    return $user;
}

function db_has_column(string $table, string $column): bool {
    global $conexao;
    $table = mysqli_real_escape_string($conexao, $table);
    $column = mysqli_real_escape_string($conexao, $column);
    $result = mysqli_query($conexao, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function db_add_column_if_missing(string $table, string $column, string $definition): void {
    global $conexao;
    if (!db_has_column($table, $column)) {
        mysqli_query($conexao, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_app_schema(): void {
    global $conexao;

    mysqli_query($conexao, "
    CREATE TABLE IF NOT EXISTS musicas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        youtube_id VARCHAR(40) NULL,
        titulo VARCHAR(255) NOT NULL,
        artista VARCHAR(255) NOT NULL,
        thumbnail TEXT NULL,
        data_adicao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    db_add_column_if_missing('usuarios', 'foto', 'VARCHAR(255) NULL');
    db_add_column_if_missing('musicas', 'audio_url', 'TEXT NULL');
    db_add_column_if_missing('musicas', 'album', 'VARCHAR(255) NULL');
    db_add_column_if_missing('musicas', 'genero', 'VARCHAR(80) NULL');
    db_add_column_if_missing('musicas', 'origem', "VARCHAR(40) NOT NULL DEFAULT 'youtube'");
    db_add_column_if_missing('musicas', 'data_adicao', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

    mysqli_query($conexao, "
    CREATE TABLE IF NOT EXISTS playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        nome VARCHAR(120) NOT NULL,
        descricao TEXT NULL,
        capa TEXT NULL,
        criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    mysqli_query($conexao, "
    CREATE TABLE IF NOT EXISTS playlist_musicas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        playlist_id INT NOT NULL,
        musica_id INT NOT NULL,
        adicionada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY playlist_musica (playlist_id, musica_id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
}

function user_musics(int $usuario_id, string $order = 'data_adicao DESC, id DESC'): array {
    global $conexao;
    ensure_app_schema();
    $usuario_id = (int) $usuario_id;
    $sql = "SELECT * FROM musicas WHERE usuario_id = '$usuario_id' ORDER BY $order";
    $result = mysqli_query($conexao, $sql);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function user_playlists(int $usuario_id): array {
    global $conexao;
    ensure_app_schema();
    $usuario_id = (int) $usuario_id;
    $result = mysqli_query($conexao, "SELECT * FROM playlists WHERE usuario_id = '$usuario_id' ORDER BY criada_em DESC, id DESC");
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function playlist_tracks(int $playlist_id, int $usuario_id): array {
    global $conexao;
    ensure_app_schema();
    $playlist_id = (int) $playlist_id;
    $usuario_id = (int) $usuario_id;
    $sql = "
        SELECT m.*
        FROM playlist_musicas pm
        INNER JOIN playlists p ON p.id = pm.playlist_id
        INNER JOIN musicas m ON m.id = pm.musica_id
        WHERE pm.playlist_id = '$playlist_id'
        AND p.usuario_id = '$usuario_id'
        AND m.usuario_id = '$usuario_id'
        ORDER BY pm.adicionada_em DESC
    ";
    $result = mysqli_query($conexao, $sql);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    return $items;
}

function json_tracks(array $tracks): string {
    return json_encode(array_map(function ($musica) {
        return [
            'id' => (int) $musica['id'],
            'youtube_id' => $musica['youtube_id'] ?? '',
            'audio_url' => $musica['audio_url'] ?? '',
            'titulo' => $musica['titulo'] ?? 'Sem titulo',
            'artista' => $musica['artista'] ?? 'Artista desconhecido',
            'thumbnail' => $musica['thumbnail'] ?? '',
            'genero' => $musica['genero'] ?? '',
            'album' => $musica['album'] ?? '',
        ];
    }, $tracks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function extract_youtube_id(string $url): string {
    $url = trim($url);
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) return $url;

    $parts = parse_url($url);
    if (!$parts) return '';

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $params);
        if (!empty($params['v'])) return preg_replace('/[^a-zA-Z0-9_-]/', '', $params['v']);
    }

    $host = $parts['host'] ?? '';
    $path = trim($parts['path'] ?? '', '/');

    if (strpos($host, 'youtu.be') !== false) return preg_replace('/[^a-zA-Z0-9_-]/', '', explode('/', $path)[0] ?? '');
    if (strpos($path, 'shorts/') === 0) return preg_replace('/[^a-zA-Z0-9_-]/', '', substr($path, 7));
    if (strpos($path, 'embed/') === 0) return preg_replace('/[^a-zA-Z0-9_-]/', '', substr($path, 6));

    return '';
}

// =====================================================
// NOVA FUNÇÃO: Extrai o ID de uma playlist do YouTube
// Suporta links do tipo:
//   youtube.com/playlist?list=PLxxxxxx
//   youtube.com/watch?v=xxx&list=PLxxxxxx
//   music.youtube.com/playlist?list=PLxxxxxx
// =====================================================
function extract_youtube_playlist_id(string $url): string {
    $url = trim($url);
    $parts = parse_url($url);
    if (!$parts) return '';

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $params);
        if (!empty($params['list'])) {
            // Sanitiza: apenas letras, números, hífens e underscores
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $params['list']);
        }
    }

    return '';
}


function fetch_youtube_metadata(string $youtube_id): array {
    $fallback = [
        'titulo' => '',
        'artista' => '',
        'thumbnail' => "https://i.ytimg.com/vi/$youtube_id/hqdefault.jpg",
    ];

    if ($youtube_id === '') return $fallback;

    $url = 'https://www.youtube.com/oembed?format=json&url=' . urlencode('https://www.youtube.com/watch?v=' . $youtube_id);
    $json = @file_get_contents($url);
    if (!$json) return $fallback;

    $data = json_decode($json, true);
    if (!is_array($data)) return $fallback;

    $raw_title = $data['title'] ?? '';
    $artist = $data['author_name'] ?? '';
    $title = $raw_title;

    // Check if the title contains a hyphen (with optional surrounding spaces) to split into artist - title
    if (preg_match('/^(.*?)\s+[-–—|]\s+(.*)$/', $raw_title, $matches)) {
        $artist = trim($matches[1]);
        $title = trim($matches[2]);
    }

    // Clean up common suffixes like (Official Video), [Official Audio], etc. from title
    $title = preg_replace('/\s*[([][^)]*?(Official|Video|Audio|Lyric|Lirica|HD|4K|Clip|M\/V|Music|Videoclipe)[^(]*?[\])]/i', '', $title);
    $title = trim($title);

    // Clean up common suffixes from artist if needed
    $artist = preg_replace('/\s+-\s+Topic$/i', '', $artist); // Remove YouTube Auto-generated Topic suffix

    return [
        'titulo' => $title,
        'artista' => $artist,
        'thumbnail' => $data['thumbnail_url'] ?? $fallback['thumbnail'],
    ];
}

function upload_dir(string $subdir): string {
    $dir = dirname(__DIR__) . '/uploads/' . trim($subdir, '/');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function public_upload_path(string $absolute): string {
    return str_replace('\\', '/', str_replace(dirname(__DIR__) . '/', '', $absolute));
}

function safe_filename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $name);
    return trim($name, '-') ?: 'arquivo';
}

ensure_app_schema();
?>
