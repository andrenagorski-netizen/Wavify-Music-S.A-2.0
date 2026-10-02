<?php
require_once 'app.php';
ensure_auth();

$usuario_id = (int) $_SESSION['usuario_id'];
global $conexao;

$tipo_upload = trim($_POST['tipo_upload'] ?? 'single'); // 'single' ou 'album'
$artista     = trim($_POST['artista'] ?? '');
$genero      = trim($_POST['genero'] ?? 'Electronic');
$album       = trim($_POST['album'] ?? '');

if ($artista === '') {
    $artista = 'Artista Desconhecido';
}

$capa_path = '';

// 1. Processar imagem de capa
if (isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath   = $_FILES['capa']['tmp_name'];
    $fileName      = $_FILES['capa']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExtension, $allowedExtensions)) {
        $uploadFileDir = upload_dir('covers');
        $newFileName   = 'cover_' . uniqid() . '_' . time() . '.' . $fileExtension;
        $dest_path     = $uploadFileDir . '/' . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $capa_path = public_upload_path($dest_path);
        }
    }
}

if ($capa_path === '') {
    $capa_path = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAE8qW-12I3R5AbdBkyMrbYsfIWcnx5Hfw7KtsNTJPyMHUVYOT7iFXhM2dv-zHwAK_Skvb-MKyyDDkSYTrF7Cs-qwLmp2_Xe-RShetIuoBXsmXdFXlzOC-aqEMkwddXuICfYFY-_gYJcYek11EVxyz8Wp9q04wfaJa6jHxjspngBcOoZWnSlW12xjgXM5VwJ6v0mv7UqZwFbpgoTUbogyiccDWk-UaPs-kncYghGeyMUvFDowiUoxAKdFMOuHEYT0GMaVSe3kjDCZM';
}

// ============================================================
// 2. Upload de faixa individual
// ============================================================
if ($tipo_upload === 'single') {
    $titulo = trim($_POST['titulo'] ?? '');
    if ($titulo === '') {
        $titulo = 'Faixa Sem Nome';
    }

    if (!isset($_FILES['musica']) || $_FILES['musica']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../wavify_upload.php?erro=" . urlencode("Selecione um arquivo de áudio válido."));
        exit;
    }

    $fileTmpPath   = $_FILES['musica']['tmp_name'];
    $fileName      = $_FILES['musica']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['mp3', 'wav', 'flac', 'ogg', 'aac', 'm4a'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        header("Location: ../wavify_upload.php?erro=" . urlencode("Extensão de áudio não suportada. Use MP3, WAV, FLAC ou OGG."));
        exit;
    }

    $uploadFileDir = upload_dir('audio');
    $newFileName   = 'track_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $dest_path     = $uploadFileDir . '/' . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $audio_url = public_upload_path($dest_path);

        $stmt = mysqli_prepare($conexao, "
            INSERT INTO musicas (usuario_id, youtube_id, audio_url, titulo, artista, thumbnail, album, genero, origem)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 'upload')
        ");
        mysqli_stmt_bind_param($stmt, "issssss", $usuario_id, $audio_url, $titulo, $artista, $capa_path, $album, $genero);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: ../wavify_my_library.php?sucesso=" . urlencode("Faixa '$titulo' enviada com sucesso!"));
        } else {
            header("Location: ../wavify_upload.php?erro=" . urlencode("Erro ao registrar música no banco de dados."));
        }
    } else {
        header("Location: ../wavify_upload.php?erro=" . urlencode("Erro ao salvar o arquivo de áudio."));
    }
    exit;
}

// ============================================================
// 3. Upload de álbum — salva em subpasta e cria playlist
// ============================================================
if ($tipo_upload === 'album') {

    if ($album === '') {
        $album = 'Album_' . date('Ymd_His');
    }

    if (!isset($_FILES['musicas']) || count($_FILES['musicas']['name']) === 0 || $_FILES['musicas']['error'][0] !== UPLOAD_ERR_OK) {
        header("Location: ../wavify_upload.php?erro=" . urlencode("Selecione um ou mais arquivos de áudio para o álbum."));
        exit;
    }

    // --- Criar subpasta nomeada pelo álbum em uploads/audio/<nome-album>/ ---
    $album_folder_name = preg_replace('/[^\w\-]/u', '_', $album);
    $album_folder_name = trim($album_folder_name, '_') ?: 'album_' . time();
    $album_audio_dir   = upload_dir('audio/' . $album_folder_name);

    // --- Garantir coluna 'tipo' na tabela playlists ---
    db_add_column_if_missing('playlists', 'tipo', "VARCHAR(20) NOT NULL DEFAULT 'playlist'");

    // --- Criar a playlist do álbum ---
    $stmt_playlist = mysqli_prepare($conexao,
        "INSERT INTO playlists (usuario_id, nome, descricao, capa, tipo) VALUES (?, ?, ?, ?, 'album')"
    );
    $descricao = "Álbum de " . $artista;
    mysqli_stmt_bind_param($stmt_playlist, "isss", $usuario_id, $album, $descricao, $capa_path);
    mysqli_stmt_execute($stmt_playlist);
    $playlist_id = mysqli_insert_id($conexao);

    if (!$playlist_id) {
        header("Location: ../wavify_upload.php?erro=" . urlencode("Erro ao criar a playlist do álbum no banco de dados."));
        exit;
    }

    $total_files       = count($_FILES['musicas']['name']);
    $success_count     = 0;
    $allowedExtensions = ['mp3', 'wav', 'flac', 'ogg', 'aac', 'm4a'];

    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['musicas']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $fileTmpPath   = $_FILES['musicas']['tmp_name'][$i];
        $fileName      = $_FILES['musicas']['name'][$i];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            continue; // Extensão inválida — pula
        }

        // Título extraído do nome do arquivo
        $titulo = pathinfo($fileName, PATHINFO_FILENAME);
        $titulo = str_replace(['_', '-'], ' ', $titulo);
        $titulo = ucwords(trim($titulo));

        // Nome do arquivo de destino mantém o nome original (limpo)
        $safe_name   = preg_replace('/[^\w\.\-]/u', '_', $fileName);
        $safe_name   = preg_replace('/_+/', '_', $safe_name);
        $newFileName = sprintf('%d_%s', ($i + 1), $safe_name);
        $dest_path   = $album_audio_dir . '/' . $newFileName;

        // Evitar colisão de nomes
        if (file_exists($dest_path)) {
            $newFileName = sprintf('%d_%s_%s', ($i + 1), uniqid(), $safe_name);
            $dest_path   = $album_audio_dir . '/' . $newFileName;
        }

        if (!move_uploaded_file($fileTmpPath, $dest_path)) {
            continue; // Falhou ao mover — pula
        }

        $audio_url = public_upload_path($dest_path);

        // Inserir na tabela musicas
        $stmt_musica = mysqli_prepare($conexao, "
            INSERT INTO musicas (usuario_id, youtube_id, audio_url, titulo, artista, thumbnail, album, genero, origem)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 'upload')
        ");
        mysqli_stmt_bind_param($stmt_musica, "issssss", $usuario_id, $audio_url, $titulo, $artista, $capa_path, $album, $genero);
        mysqli_stmt_execute($stmt_musica);
        $musica_id = mysqli_insert_id($conexao);

        if (!$musica_id) {
            continue;
        }

        // Associar faixa à playlist
        $stmt_assoc = mysqli_prepare($conexao,
            "INSERT IGNORE INTO playlist_musicas (playlist_id, musica_id) VALUES (?, ?)"
        );
        mysqli_stmt_bind_param($stmt_assoc, "ii", $playlist_id, $musica_id);
        mysqli_stmt_execute($stmt_assoc);

        $success_count++;
    }

    if ($success_count > 0) {
        // Redireciona direto para a playlist/álbum criado
        header("Location: ../wavify_playlists.php?id={$playlist_id}&sucesso=" . urlencode("Álbum '$album' criado com $success_count faixas salvas em uploads/audio/{$album_folder_name}/!"));
    } else {
        // Nenhuma faixa salva — apaga a playlist vazia
        mysqli_query($conexao, "DELETE FROM playlists WHERE id = " . (int)$playlist_id);
        header("Location: ../wavify_upload.php?erro=" . urlencode("Nenhuma faixa de áudio pôde ser enviada. Verifique os arquivos selecionados."));
    }
    exit;
}
