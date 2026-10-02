<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

global $conexao;
ensure_app_schema();

$capa_padrao = 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=300&auto=format&fit=crop';

// Check if a specific playlist is selected
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewing_playlist = null;
$tracks = [];

if ($playlist_id > 0) {
    // Get playlist details
    $stmt = mysqli_prepare($conexao, 'SELECT * FROM playlists WHERE id = ? AND usuario_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $playlist_id, $usuario_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $viewing_playlist = mysqli_fetch_assoc($res);
    
    if ($viewing_playlist) {
        $tracks = playlist_tracks($playlist_id, $usuario_id);
    } else {
        // Redirect if not found
        header("Location: wavify_playlists.php");
        exit;
    }
}

// Fetch all playlists (with track counts) for list view
$sql_playlists = "
    SELECT p.*, COUNT(pm.musica_id) as total_musicas 
    FROM playlists p 
    LEFT JOIN playlist_musicas pm ON pm.playlist_id = p.id 
    WHERE p.usuario_id = '$usuario_id' 
    GROUP BY p.id 
    ORDER BY p.criada_em DESC, p.id DESC
";
$res_playlists = mysqli_query($conexao, $sql_playlists);
$playlists = [];
while ($row = mysqli_fetch_assoc($res_playlists)) {
    $playlists[] = $row;
}

// Fetch all library musics to allow adding to the playlist
$todas_musicas = user_musics($usuario_id);

// Start page rendering
// If viewing playlist detail, load the playlist tracks into the player, otherwise load all user musics
$player_tracks = ($viewing_playlist && count($tracks) > 0) ? $tracks : $todas_musicas;
render_page_start($viewing_playlist ? 'WAVIFY - ' . htmlspecialchars($viewing_playlist['nome']) : 'WAVIFY - Playlists', 'wavify_playlists.php', $usuario, $player_tracks);

$msg_sucesso = trim($_GET['sucesso'] ?? '');
$msg_erro    = trim($_GET['erro'] ?? '');
?>

<div class="container mx-auto pb-10">
    <?php if ($msg_sucesso !== ''): ?>
        <div class="mb-6 rounded-2xl border border-primary/30 bg-primary-container/30 px-6 py-4 text-primary font-body-md flex items-center gap-3">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">check_circle</span>
            <?= htmlspecialchars($msg_sucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($msg_erro !== ''): ?>
        <div class="mb-6 rounded-2xl border border-error/30 bg-error-container/30 px-6 py-4 text-error font-body-md flex items-center gap-3">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">error</span>
            <?= htmlspecialchars($msg_erro) ?>
        </div>
    <?php endif; ?>
    <?php if ($viewing_playlist): ?>

        <!-- ========================================== -->
        <!-- PLAYLIST DETAIL VIEW                       -->
        <!-- ========================================== -->
        
        <!-- Breadcrumb / Header Navigation -->
        <div class="flex items-center gap-3 mb-8">
            <a href="wavify_playlists.php" class="flex items-center gap-1 text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                Voltar para Playlists
            </a>
        </div>

        <!-- Playlist Info Header Banner -->
        <header class="flex flex-col md:flex-row gap-8 items-end mb-10 pb-8 border-b border-white/10 relative">
            <div class="absolute -top-10 -left-10 w-72 h-72 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Playlist Cover (Large) -->
            <div class="relative w-48 h-48 md:w-56 md:h-56 rounded-2xl overflow-hidden shadow-2xl group flex-shrink-0 self-center md:self-auto border border-white/10">
                <img src="<?= htmlspecialchars($viewing_playlist['capa'] ?: $capa_padrao) ?>" class="w-full h-full object-cover" alt="Capa da Playlist">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer" onclick="openEditModal(<?= $viewing_playlist['id'] ?>, <?= htmlspecialchars(json_encode($viewing_playlist['nome']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($viewing_playlist['descricao']), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($viewing_playlist['capa']) ?>')">
                    <span class="material-symbols-outlined text-white text-3xl">edit</span>
                </div>
            </div>

            <!-- Meta info -->
            <div class="flex-1 text-center md:text-left">
                <span class="font-label-md text-label-md <?= ($viewing_playlist['tipo'] ?? '') === 'album' ? 'text-secondary' : 'text-primary' ?> uppercase tracking-widest">
                    <?= ($viewing_playlist['tipo'] ?? '') === 'album' ? '🎵 Álbum' : 'Playlist' ?>
                </span>
                <h1 class="font-headline-xl text-4xl md:text-6xl font-bold text-on-surface mt-2 mb-4 leading-tight">
                    <?= htmlspecialchars($viewing_playlist['nome']) ?>
                </h1>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6 max-w-2xl">
                    <?= htmlspecialchars($viewing_playlist['descricao'] ?: 'Sem descrição.') ?>
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-4 gap-y-2 text-sm text-on-surface-variant font-medium">
                    <span class="text-on-surface font-semibold"><?= htmlspecialchars($usuario['usuario']) ?></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                    <span><?= count($tracks) ?> <?= count($tracks) === 1 ? 'música' : 'músicas' ?></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                    <span>Criada em <?= date('d/m/Y', strtotime($viewing_playlist['criada_em'])) ?></span>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex flex-wrap items-center justify-center md:justify-end gap-3 w-full md:w-auto">
                <?php if (count($tracks) > 0): ?>
                    <button onclick="ouvirTudoPlaylist()" class="flex items-center justify-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-primary-container to-inverse-primary text-white font-label-lg text-label-lg shadow-[0_0_20px_rgba(124,58,237,0.4)] hover:shadow-[0_0_30px_rgba(124,58,237,0.6)] hover:scale-[1.03] active:scale-[0.98] transition-all">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                        Ouvir Playlist
                    </button>
                <?php endif; ?>
                <button onclick="openEditModal(<?= $viewing_playlist['id'] ?>, <?= htmlspecialchars(json_encode($viewing_playlist['nome']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($viewing_playlist['descricao']), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($viewing_playlist['capa']) ?>')" class="flex items-center justify-center gap-2 px-5 py-3 rounded-full bg-white/5 border border-white/10 text-on-surface font-label-lg text-label-lg hover:bg-white/10 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                    Editar
                </button>
                <button onclick="confirmarExclusaoPlaylist(<?= $viewing_playlist['id'] ?>, <?= htmlspecialchars(json_encode($viewing_playlist['nome']), ENT_QUOTES, 'UTF-8') ?>)" class="flex items-center justify-center gap-2 px-5 py-3 rounded-full bg-error/10 border border-error/20 text-error font-label-lg text-label-lg hover:bg-error/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                    Excluir
                </button>
            </div>
        </header>

        <!-- Track List Section -->
        <section class="mb-12">
            <h2 class="font-headline-md text-2xl text-on-surface mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[28px]">music_note</span>
                Músicas da Playlist
            </h2>
            
            <div class="bg-surface-container-low/50 backdrop-blur-md rounded-xl border border-white/5 overflow-hidden">
                <!-- Table Header -->
                <div class="grid grid-cols-[auto_1fr_1fr_auto] gap-4 px-6 py-4 border-b border-white/5 text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
                    <div class="w-8 text-center">#</div>
                    <div>Título</div>
                    <div class="hidden md:block">Artista</div>
                    <div class="w-24 text-right">Ação</div>
                </div>

                <!-- List -->
                <div class="flex flex-col">
                    <?php if (count($tracks) > 0): ?>
                        <?php foreach($tracks as $indice => $musica): ?>
                            <div 
                                id="playlist-track-item-<?= $musica['id'] ?>"
                                class="group grid grid-cols-[auto_1fr_1fr_auto] gap-4 px-6 py-3 items-center hover:bg-white/5 transition-colors cursor-pointer border-b border-white/5 last:border-0 relative"
                                onclick="tocarMusicaPorIndice(<?= $indice ?>)"
                            >
                                <div class="w-8 text-center font-bold text-on-surface-variant group-hover:text-primary">
                                    <?= $indice + 1 ?>
                                </div>

                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-12 h-12 rounded overflow-hidden flex-shrink-0">
                                        <img src="<?= htmlspecialchars($musica['thumbnail'] ?: $capa_padrao) ?>" class="w-full h-full object-cover" alt="Thumbnail">
                                    </div>
                                    <div class="truncate">
                                        <span class="block font-body-md text-on-surface truncate group-hover:text-primary transition-colors">
                                            <?= htmlspecialchars($musica['titulo']) ?>
                                        </span>
                                        <span class="block font-body-sm text-on-surface-variant md:hidden truncate">
                                            <?= htmlspecialchars($musica['artista']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="hidden md:block truncate">
                                    <?= htmlspecialchars($musica['artista']) ?>
                                </div>

                                <div class="w-24 text-right flex items-center justify-end gap-2" onclick="event.stopPropagation();">
                                    <!-- Play inline icon -->
                                    <button class="w-8 h-8 rounded-full bg-primary/10 text-primary opacity-0 group-hover:opacity-100 hover:bg-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center justify-center" onclick="tocarMusicaPorIndice(<?= $indice ?>)" title="Tocar música">
                                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                                    </button>
                                    <!-- Remove track from playlist -->
                                    <button class="w-8 h-8 rounded-full bg-error/10 text-error opacity-0 group-hover:opacity-100 hover:bg-error/20 hover:scale-105 active:scale-95 transition-all flex items-center justify-center" onclick="removerMusicaDaPlaylist(<?= $viewing_playlist['id'] ?>, <?= $musica['id'] ?>, <?= htmlspecialchars(json_encode($musica['titulo']), ENT_QUOTES, 'UTF-8') ?>)" title="Remover da Playlist">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="px-6 py-12 text-on-surface-variant text-center">
                            <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant/40 block">playlist_play</span>
                            Esta playlist está vazia. Adicione músicas abaixo!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ADD MUSIC SECTION -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Form A: Add directly via YouTube link -->
            <div class="bg-surface-container-low/50 backdrop-blur-md rounded-2xl border border-white/5 p-8 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-primary text-[28px]">smart_display</span>
                        <h3 class="font-headline-sm text-xl font-bold text-on-surface">Adicionar Direto do YouTube</h3>
                    </div>
                    <p class="font-body-md text-on-surface-variant mb-6 leading-relaxed">
                        Cole um link do YouTube. A música será salva na sua biblioteca e adicionada automaticamente a esta playlist.
                    </p>
                </div>
                
                <form id="form-youtube-playlist" class="flex flex-col gap-4 relative z-10">
                    <input type="hidden" name="playlist_id" value="<?= $viewing_playlist['id'] ?>">
                    
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors text-[20px]">link</span>
                        <input
                            type="text"
                            name="youtube_link"
                            id="youtube_link_playlist"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 pl-12 pr-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                            required
                        />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input
                            type="text"
                            name="titulo"
                            id="youtube_title_playlist"
                            placeholder="Nome da música (opcional)"
                            class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 px-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                        />
                        <input
                            type="text"
                            name="artista"
                            id="youtube_artist_playlist"
                            placeholder="Nome do artista (opcional)"
                            class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 px-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                        />
                    </div>

                    <button
                        type="submit"
                        class="flex items-center justify-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-primary-container to-inverse-primary text-white font-label-lg text-label-lg shadow-[0_0_20px_rgba(124,58,237,0.4)] hover:shadow-[0_0_30px_rgba(124,58,237,0.7)] hover:scale-[1.03] active:scale-[0.98] transition-all duration-300 mt-2"
                    >
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        Salvar e Adicionar
                    </button>
                </form>
            </div>

            <!-- Form B: Add from saved library songs -->
            <div class="bg-surface-container-low/50 backdrop-blur-md rounded-2xl border border-white/5 p-8 relative overflow-hidden flex flex-col">
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-secondary/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-secondary text-[28px]">library_music</span>
                    <h3 class="font-headline-sm text-xl font-bold text-on-surface">Adicionar Músicas Salvas</h3>
                </div>

                <!-- Live search filter -->
                <div class="relative mb-6">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                    <input 
                        type="text" 
                        id="search-library-songs" 
                        placeholder="Filtrar músicas da sua biblioteca..." 
                        class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-2.5 pl-12 pr-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-sm outline-none transition-all duration-300"
                    >
                </div>

                <!-- Scrollable library list -->
                <div class="flex-1 max-h-60 overflow-y-auto pr-2 gap-2 flex flex-col hide-scrollbar" id="library-songs-container">
                    <?php 
                    // Filter out songs already in the playlist to make it easier
                    $existing_ids = array_column($tracks, 'id');
                    $available_songs = array_filter($todas_musicas, function($m) use ($existing_ids) {
                        return !in_array($m['id'], $existing_ids);
                    });
                    
                    if (count($available_songs) > 0): 
                        foreach($available_songs as $musica): ?>
                            <div class="flex items-center justify-between gap-4 p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 transition-all duration-200 library-song-row" data-title="<?= htmlspecialchars(strtolower($musica['titulo'])) ?>" data-artist="<?= htmlspecialchars(strtolower($musica['artista'])) ?>">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img src="<?= htmlspecialchars($musica['thumbnail'] ?: $capa_padrao) ?>" class="w-10 h-10 rounded object-cover flex-shrink-0" alt="Capa">
                                    <div class="truncate">
                                        <span class="block font-bold text-sm text-on-surface truncate"><?= htmlspecialchars($musica['titulo']) ?></span>
                                        <span class="block text-xs text-on-surface-variant truncate"><?= htmlspecialchars($musica['artista']) ?></span>
                                    </div>
                                </div>
                                <button onclick="adicionarMusicaSalva(<?= $viewing_playlist['id'] ?>, <?= $musica['id'] ?>, <?= htmlspecialchars(json_encode($musica['titulo']), ENT_QUOTES, 'UTF-8') ?>, this)" class="px-4 py-1.5 rounded-full bg-primary/10 text-primary font-bold text-xs hover:bg-primary/20 transition-all flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">add</span>
                                    Adicionar
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-on-surface-variant text-center py-8 text-sm">
                            Nenhuma música disponível para adicionar.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    <?php else: ?>
        <!-- ========================================== -->
        <!-- PLAYLISTS LIST GRID VIEW                   -->
        <!-- ========================================== -->
        <header class="mb-10">
            <h1 class="page-title mb-6">Minha Biblioteca</h1>
            <!-- Tabs -->
            <div class="flex gap-8 border-b border-white/10">
                <a href="wavify_my_library.php" class="pb-4 font-label-lg text-label-lg text-on-surface-variant hover:text-on-surface transition-colors tracking-widest">
                    MÚSICAS
                </a>
                <button class="pb-4 font-label-lg text-label-lg text-primary border-b-2 border-primary shadow-[0_10px_20px_-10px_rgba(124,58,237,0.5)] tracking-widest">
                    PLAYLISTS
                </button>
            </div>
        </header>

        <!-- Grid de Playlists: 2 cards de ação + playlists do usuário -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
            
            <!-- Card: Criar Nova Playlist -->
            <div 
                onclick="openCreateModal()"
                class="group relative flex flex-col justify-center items-center aspect-square rounded-2xl border-2 border-dashed border-white/20 hover:border-primary/60 cursor-pointer bg-white/[0.02] hover:bg-white/[0.05] transition-all duration-300"
            >
                <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:scale-110 group-hover:bg-primary/20 shadow-[0_0_20px_rgba(124,58,237,0)] group-hover:shadow-[0_0_25px_rgba(124,58,237,0.3)] transition-all">
                    <span class="material-symbols-outlined text-4xl">add</span>
                </div>
                <span class="font-headline-sm text-on-surface text-center font-bold mt-4 group-hover:text-primary transition-colors">
                    Criar Playlist
                </span>
            </div>

            <!-- Card: Importar Playlist do YouTube -->
            <!-- NOVA FUNCIONALIDADE: Colar o link de uma playlist do YouTube/YouTube Music
                 para salvar todas as músicas na biblioteca e criar a playlist automática -->
            <div 
                onclick="openImportPlaylistModal()"
                class="group relative flex flex-col justify-center items-center aspect-square rounded-2xl border-2 border-dashed border-secondary/30 hover:border-secondary/70 cursor-pointer bg-white/[0.02] hover:bg-secondary/[0.05] transition-all duration-300"
            >
                <div class="w-16 h-16 rounded-full bg-secondary/10 text-secondary flex items-center justify-center group-hover:scale-110 group-hover:bg-secondary/20 shadow-[0_0_20px_rgba(255,185,95,0)] group-hover:shadow-[0_0_25px_rgba(255,185,95,0.3)] transition-all">
                    <span class="material-symbols-outlined text-4xl">download</span>
                </div>
                <span class="font-headline-sm text-on-surface text-center font-bold mt-4 text-[12px] group-hover:text-secondary transition-colors px-2">
                    Importar do YouTube
                </span>
            </div>

            <!-- Existing Playlists -->
            <?php if (count($playlists) > 0): ?>
                <?php foreach($playlists as $playlist): ?>
                    <div class="group relative bg-surface-container-low/40 backdrop-blur-sm border border-white/5 hover:border-primary/40 rounded-2xl p-4 transition-all duration-300 flex flex-col h-full">
                        <!-- Cover Art Container -->
                        <a href="wavify_playlists.php?id=<?= $playlist['id'] ?>" class="relative aspect-square w-full rounded-xl overflow-hidden mb-4 border border-white/5 block">
                            <img 
                                src="<?= htmlspecialchars($playlist['capa'] ?: $capa_padrao) ?>" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                alt="Capa de <?= htmlspecialchars($playlist['nome']) ?>"
                            >
                            
                            <!-- Play Overlay on Hover -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[1px]">
                                <span onclick="event.preventDefault(); event.stopPropagation(); window.ouvirTudoDaPlaylistCard(<?= $playlist['id'] ?>)" class="w-12 h-12 bg-primary rounded-full flex items-center justify-center shadow-[0_0_20px_rgba(124,58,237,0.6)] translate-y-4 group-hover:translate-y-0 transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer">
                                    <span class="material-symbols-outlined text-on-primary-fixed text-[28px]" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                                </span>
                            </div>
                        </a>

                        <!-- Info -->
                        <div class="flex-1 flex flex-col justify-between min-w-0">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <a href="wavify_playlists.php?id=<?= $playlist['id'] ?>" class="font-label-lg text-label-lg text-on-surface truncate group-hover:text-primary transition-colors block max-w-full">
                                        <?= htmlspecialchars($playlist['nome']) ?>
                                    </a>
                                    <?php if (($playlist['tipo'] ?? '') === 'album'): ?>
                                        <span style="font-size:10px;font-weight:700;letter-spacing:0.06em;padding:2px 7px;border-radius:999px;background:rgba(255,153,0,0.18);color:#ffb95f;border:1px solid rgba(255,185,95,0.3);white-space:nowrap;flex-shrink:0;">ÁLBUM</span>
                                    <?php endif; ?>
                                </div>
                                <p class="font-body-sm text-xs text-on-surface-variant truncate mt-1">
                                    <?= htmlspecialchars($playlist['descricao'] ?: 'Sem descrição') ?>
                                </p>
                            </div>

                            
                            <!-- Track count & edit menu -->
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/5 text-[11px] text-on-surface-variant font-medium">
                                <span>
                                    <?= $playlist['total_musicas'] ?> <?= $playlist['total_musicas'] == 1 ? 'música' : 'músicas' ?>
                                </span>
                                
                                <div class="flex items-center gap-1" onclick="event.stopPropagation();">
                                    <a href="wavify_playlists.php?id=<?= $playlist['id'] ?>" class="hover:text-secondary p-1 transition-colors flex items-center" title="Ver Músicas da Playlist">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    </a>
                                    <button onclick="openEditModal(<?= $playlist['id'] ?>, <?= htmlspecialchars(json_encode($playlist['nome']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($playlist['descricao']), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($playlist['capa']) ?>')" class="hover:text-primary p-1 transition-colors" title="Editar playlist">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <button onclick="confirmarExclusaoPlaylist(<?= $playlist['id'] ?>, <?= htmlspecialchars(json_encode($playlist['nome']), ENT_QUOTES, 'UTF-8') ?>)" class="hover:text-error p-1 transition-colors" title="Excluir playlist">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- MODAL: Importar Playlist do YouTube        -->
<!-- NOVA FUNCIONALIDADE adicionada              -->
<!-- ========================================== -->
<div id="modal-importar-playlist" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="modal-card bg-surface-container border border-white/10 p-6 md:p-8 rounded-3xl max-w-lg w-full shadow-2xl transform scale-95 transition-all duration-300 relative overflow-hidden text-on-surface">
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <!-- Cabeçalho do modal -->
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center text-secondary shrink-0">
                <span class="material-symbols-outlined text-[24px]">download</span>
            </div>
            <div>
                <h3 class="font-headline-sm text-xl font-bold">Importar Playlist do YouTube</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Todas as músicas serão salvas na sua biblioteca</p>
            </div>
        </div>

        <!-- Instruções -->
        <div class="bg-secondary/5 border border-secondary/20 rounded-xl px-4 py-3 mb-5 mt-4">
            <p class="text-xs text-on-surface-variant leading-relaxed">
                <span class="text-secondary font-bold">Como usar:</span> Copie o link de qualquer playlist pública do YouTube ou YouTube Music e cole abaixo. Todas as músicas serão adicionadas à sua biblioteca e uma playlist será criada automaticamente.
            </p>
        </div>

        <form id="form-importar-playlist" class="flex flex-col gap-4">
            <!-- Campo: Link da playlist -->
            <div class="flex flex-col gap-1">
                <label for="import-playlist-link" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Link da Playlist *</label>
                <div class="relative group">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-secondary transition-colors text-[20px]">link</span>
                    <input 
                        type="text"
                        id="import-playlist-link"
                        name="playlist_link"
                        placeholder="https://www.youtube.com/playlist?list=PL..."
                        required
                        class="w-full bg-surface-container-high border border-white/10 focus:border-secondary/60 focus:ring-2 focus:ring-secondary/30 rounded-xl py-3 pl-12 pr-4 text-on-surface placeholder:text-on-surface-variant/40 outline-none transition-all duration-200"
                    >
                </div>
                <!-- Validação em tempo real -->
                <p id="import-link-error" class="text-xs text-error hidden mt-1">Link inválido. Certifique-se que contém "list=" na URL.</p>
            </div>

            <!-- Campo: Nome personalizado (opcional) -->
            <div class="flex flex-col gap-1">
                <label for="import-playlist-nome" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Nome da Playlist (opcional)</label>
                <input 
                    type="text"
                    id="import-playlist-nome"
                    name="nome"
                    placeholder="Deixe em branco para usar o nome original do YouTube"
                    class="w-full bg-surface-container-high border border-white/10 focus:border-secondary/60 focus:ring-2 focus:ring-secondary/30 rounded-xl py-3 px-4 text-on-surface placeholder:text-on-surface-variant/40 outline-none transition-all duration-200"
                >
            </div>

            <!-- Barra de progresso (oculta até importar) -->
            <div id="import-progress-container" class="hidden">
                <div class="flex items-center justify-between text-xs text-on-surface-variant mb-2">
                    <span id="import-progress-label" class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary animate-spin text-[16px]">sync</span>
                        Buscando músicas na API do YouTube...
                    </span>
                </div>
                <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                    <div id="import-progress-bar" class="h-full bg-gradient-to-r from-secondary to-primary rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-2">
                <button type="button" onclick="closeImportPlaylistModal()" class="px-5 py-2.5 rounded-full font-label-lg text-sm text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all">
                    Cancelar
                </button>
                <button type="submit" id="btn-import-submit" class="px-6 py-2.5 rounded-full font-label-lg text-sm bg-gradient-to-r from-secondary to-primary text-on-primary-fixed hover:shadow-lg hover:shadow-secondary/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Importar Playlist
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Criar Playlist -->
<div id="modal-criar-playlist" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="modal-card bg-surface-container border border-white/10 p-6 md:p-8 rounded-3xl max-w-lg w-full shadow-2xl transform scale-95 transition-all duration-300 relative overflow-hidden text-on-surface">
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-[24px]">playlist_add</span>
            </div>
            <h3 class="font-headline-sm text-xl font-bold">Criar Nova Playlist</h3>
        </div>

        <form id="form-criar-playlist" class="flex flex-col gap-4" enctype="multipart/form-data">
            <div class="flex flex-col gap-1">
                <label for="create-playlist-name" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Nome da Playlist *</label>
                <input 
                    type="text" 
                    id="create-playlist-name" 
                    name="nome" 
                    placeholder="Minha Playlist Favorita" 
                    required 
                    class="w-full bg-surface-container-high border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-xl py-3 px-4 text-on-surface outline-none transition-all duration-200"
                >
            </div>

            <div class="flex flex-col gap-1">
                <label for="create-playlist-desc" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Descrição</label>
                <textarea 
                    id="create-playlist-desc" 
                    name="descricao" 
                    placeholder="Descrição desta playlist..." 
                    rows="3" 
                    class="w-full bg-surface-container-high border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-xl py-3 px-4 text-on-surface outline-none resize-none transition-all duration-200"
                ></textarea>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Imagem de Capa (opcional)</label>
                <div class="flex items-center gap-4 mt-1">
                    <div class="w-16 h-16 rounded-xl bg-surface-container-high border border-white/10 overflow-hidden flex items-center justify-center shrink-0" id="create-cover-preview">
                        <span class="material-symbols-outlined text-on-surface-variant text-[28px]">image</span>
                    </div>
                    <label class="px-4 py-2 bg-white/5 border border-white/10 hover:bg-white/10 rounded-full font-label-md text-xs cursor-pointer text-on-surface transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                        Enviar Capa
                        <input type="file" name="capa" accept="image/*" class="hidden" onchange="previewImage(this, 'create-cover-preview')">
                    </label>
                </div>
            </div>

            <!-- Selecionar Músicas da Biblioteca -->
            <div class="flex flex-col gap-1.5 mt-2">
                <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center justify-between">
                    <span>Selecionar Músicas (opcional)</span>
                    <span class="text-[10px] text-primary" id="count-checked-songs">0 selecionadas</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[16px]">search</span>
                    <input 
                        type="text" 
                        id="search-modal-songs" 
                        placeholder="Pesquisar na biblioteca..." 
                        class="w-full bg-surface-container-high border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-xl py-2 pl-9 pr-4 text-on-surface placeholder:text-on-surface-variant/40 font-body-sm text-xs outline-none transition-all duration-200"
                    >
                </div>
                <div class="max-h-40 overflow-y-auto pr-1 mt-2 gap-2 flex flex-col hide-scrollbar border border-white/5 p-2 rounded-xl bg-surface-container-high/40" id="modal-songs-container">
                    <?php if (count($todas_musicas) > 0): ?>
                        <?php foreach($todas_musicas as $musica): ?>
                            <label class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition-all modal-song-row" data-title="<?= htmlspecialchars(strtolower($musica['titulo'])) ?>" data-artist="<?= htmlspecialchars(strtolower($musica['artista'])) ?>">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input 
                                        type="checkbox" 
                                        name="musicas[]" 
                                        value="<?= $musica['id'] ?>" 
                                        class="rounded border-white/20 text-primary focus:ring-primary/30 bg-surface-container-high w-4 h-4 cursor-pointer checkbox-modal-song"
                                        onchange="updateCheckedCount()"
                                    >
                                    <img src="<?= htmlspecialchars($musica['thumbnail'] ?: $capa_padrao) ?>" class="w-8 h-8 rounded object-cover flex-shrink-0" alt="Capa">
                                    <div class="truncate">
                                        <span class="block font-bold text-xs text-on-surface truncate"><?= htmlspecialchars($musica['titulo']) ?></span>
                                        <span class="block text-[10px] text-on-surface-variant truncate"><?= htmlspecialchars($musica['artista']) ?></span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-on-surface-variant text-center py-6 text-xs">
                            Sua biblioteca está vazia. Salve músicas primeiro!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 rounded-full font-label-lg text-sm text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-full font-label-lg text-sm bg-gradient-to-r from-primary to-secondary text-on-primary-fixed hover:shadow-lg hover:shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Criar Playlist
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Playlist -->
<div id="modal-editar-playlist" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="modal-card bg-surface-container border border-white/10 p-6 md:p-8 rounded-3xl max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 relative overflow-hidden text-on-surface">
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-[24px]">edit</span>
            </div>
            <h3 class="font-headline-sm text-xl font-bold">Editar Playlist</h3>
        </div>

        <form id="form-editar-playlist" class="flex flex-col gap-4" enctype="multipart/form-data">
            <input type="hidden" name="playlist_id" id="edit-playlist-id">
            
            <div class="flex flex-col gap-1">
                <label for="edit-playlist-name" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Nome da Playlist *</label>
                <input 
                    type="text" 
                    id="edit-playlist-name" 
                    name="nome" 
                    required 
                    class="w-full bg-surface-container-high border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-xl py-3 px-4 text-on-surface outline-none transition-all duration-200"
                >
            </div>

            <div class="flex flex-col gap-1">
                <label for="edit-playlist-desc" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Descrição</label>
                <textarea 
                    id="edit-playlist-desc" 
                    name="descricao" 
                    rows="3" 
                    class="w-full bg-surface-container-high border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-xl py-3 px-4 text-on-surface outline-none resize-none transition-all duration-200"
                ></textarea>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Alterar Capa (opcional)</label>
                <div class="flex items-center gap-4 mt-1">
                    <div class="w-16 h-16 rounded-xl bg-surface-container-high border border-white/10 overflow-hidden flex items-center justify-center shrink-0" id="edit-cover-preview">
                        <img src="" class="w-full h-full object-cover hidden" id="edit-cover-preview-img">
                        <span class="material-symbols-outlined text-on-surface-variant text-[28px]" id="edit-cover-preview-placeholder">image</span>
                    </div>
                    <label class="px-4 py-2 bg-white/5 border border-white/10 hover:bg-white/10 rounded-full font-label-md text-xs cursor-pointer text-on-surface transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                        Alterar Capa
                        <input type="file" name="capa" accept="image/*" class="hidden" onchange="previewImage(this, 'edit-cover-preview')">
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-full font-label-lg text-sm text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-full font-label-lg text-sm bg-gradient-to-r from-primary to-secondary text-on-primary-fixed hover:shadow-lg hover:shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Prevent duplicate declaration errors when using SPA loading
(function() {
    // Expose helpers globally if not already defined
    window.previewImage = function(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0] && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    };

    window.openCreateModal = function() {
        const modal = document.getElementById('modal-criar-playlist');
        if (!modal) return;
        
        modal.classList.remove('hidden');
        document.getElementById('form-criar-playlist').reset();
        document.getElementById('create-cover-preview').innerHTML = '<span class="material-symbols-outlined text-on-surface-variant text-[28px]">image</span>';
        
        // Reset checkbox counter, search input, and filter states
        const countLabel = document.getElementById('count-checked-songs');
        if (countLabel) countLabel.textContent = '0 selecionadas';
        const searchInput = document.getElementById('search-modal-songs');
        if (searchInput) searchInput.value = '';
        const rows = document.querySelectorAll('#modal-songs-container .modal-song-row');
        rows.forEach(row => row.style.display = 'flex');
        
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-card').classList.remove('scale-95');
            modal.querySelector('.modal-card').classList.add('scale-100');
        }, 10);
    };

    window.closeCreateModal = function() {
        const modal = document.getElementById('modal-criar-playlist');
        if (!modal) return;
        
        modal.classList.add('opacity-0');
        modal.querySelector('.modal-card').classList.remove('scale-100');
        modal.querySelector('.modal-card').classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    };

    window.updateCheckedCount = function() {
        const checked = document.querySelectorAll('.checkbox-modal-song:checked').length;
        const label = document.getElementById('count-checked-songs');
        if (label) {
            label.textContent = `${checked} selecionada${checked !== 1 ? 's' : ''}`;
        }
    };

    window.openEditModal = function(id, nome, descricao, capa) {
        const modal = document.getElementById('modal-editar-playlist');
        if (!modal) return;
        
        document.getElementById('edit-playlist-id').value = id;
        document.getElementById('edit-playlist-name').value = nome;
        document.getElementById('edit-playlist-desc').value = descricao;
        
        const preview = document.getElementById('edit-cover-preview');
        if (capa && capa !== '') {
            preview.innerHTML = `<img src="${capa}" class="w-full h-full object-cover">`;
        } else {
            preview.innerHTML = '<span class="material-symbols-outlined text-on-surface-variant text-[28px]">image</span>';
        }
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-card').classList.remove('scale-95');
            modal.querySelector('.modal-card').classList.add('scale-100');
        }, 10);
    };

    window.closeEditModal = function() {
        const modal = document.getElementById('modal-editar-playlist');
        if (!modal) return;
        
        modal.classList.add('opacity-0');
        modal.querySelector('.modal-card').classList.remove('scale-100');
        modal.querySelector('.modal-card').classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    };

    window.confirmarExclusaoPlaylist = function(id, nome) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm opacity-0 transition-opacity duration-300';
        modal.innerHTML = `
            <div class="modal-card bg-surface-container border border-white/10 p-6 md:p-8 rounded-3xl max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 relative overflow-hidden text-on-surface">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-error/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-error/10 flex items-center justify-center text-error shrink-0">
                        <span class="material-symbols-outlined text-[28px]">delete_forever</span>
                    </div>
                    <h3 class="font-headline-md text-headline-sm text-on-surface">Excluir Playlist?</h3>
                </div>
                
                <p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed">
                    Tem certeza que deseja excluir a playlist <strong class="text-on-surface">"${nome}"</strong>? As músicas salvas não serão apagadas da sua biblioteca, apenas a playlist será excluída. Esta ação não pode ser desfeita.
                </p>
                
                <div class="flex justify-end gap-3">
                    <button id="btn-cancel-del-p" class="px-5 py-2.5 rounded-full font-label-lg text-label-lg text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all">
                        Cancelar
                    </button>
                    <button id="btn-confirm-del-p" class="px-6 py-2.5 rounded-full font-label-lg text-label-lg bg-gradient-to-r from-error to-red-500 hover:from-red-500 hover:to-red-600 text-white shadow-lg shadow-error/20 hover:shadow-error/30 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        Excluir Playlist
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const card = modal.querySelector('.modal-card');
        
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
        
        const fechar = () => {
            modal.classList.add('opacity-0');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
            setTimeout(() => modal.remove(), 300);
        };
        
        modal.querySelector('#btn-cancel-del-p').addEventListener('click', fechar);
        modal.addEventListener('click', (e) => { if (e.target === modal) fechar(); });
        
        modal.querySelector('#btn-confirm-del-p').addEventListener('click', () => {
            fechar();
            
            const formData = new FormData();
            formData.append('playlist_id', id);
            
            fetch('php/deletar_playlist.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback(`Playlist "${nome}" excluída com sucesso.`, 'success');
                    }
                    if (window.location.search.includes('id=')) {
                        navegarSPA('wavify_playlists.php');
                    } else {
                        navegarSPA(window.location.href);
                    }
                } else {
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback(data.erro || 'Erro ao excluir playlist.', 'error');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback('Erro de conexão ao excluir playlist.', 'error');
                }
            });
        });
    };

    // ──────────────────────────────────────────────────────────────────
    // NOVA FUNCIONALIDADE: Modal de Importar Playlist do YouTube
    // ──────────────────────────────────────────────────────────────────

    // Abre o modal de importação
    window.openImportPlaylistModal = function() {
        const modal = document.getElementById('modal-importar-playlist');
        if (!modal) return;

        // Reseta o formulário
        document.getElementById('form-importar-playlist').reset();
        document.getElementById('import-link-error').classList.add('hidden');
        document.getElementById('import-progress-container').classList.add('hidden');
        document.getElementById('import-progress-bar').style.width = '0%';

        const btn = document.getElementById('btn-import-submit');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">download</span> Importar Playlist';
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.modal-card').classList.remove('scale-95');
            modal.querySelector('.modal-card').classList.add('scale-100');
        }, 10);
    };

    // Fecha o modal de importação
    window.closeImportPlaylistModal = function() {
        const modal = document.getElementById('modal-importar-playlist');
        if (!modal) return;

        modal.classList.add('opacity-0');
        modal.querySelector('.modal-card').classList.remove('scale-100');
        modal.querySelector('.modal-card').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    };

    // Fechar ao clicar no backdrop
    const modalImportar = document.getElementById('modal-importar-playlist');
    if (modalImportar) {
        modalImportar.addEventListener('click', (e) => {
            if (e.target === modalImportar) window.closeImportPlaylistModal();
        });

        // Validação em tempo real do link
        const linkInput = document.getElementById('import-playlist-link');
        const linkError = document.getElementById('import-link-error');
        if (linkInput && linkError) {
            linkInput.addEventListener('input', () => {
                const val = linkInput.value;
                if (val.length > 10 && !val.includes('list=')) {
                    linkError.classList.remove('hidden');
                } else {
                    linkError.classList.add('hidden');
                }
            });
        }

        // Submit do formulário de importação
        const formImportar = document.getElementById('form-importar-playlist');
        if (formImportar) {
            formImportar.addEventListener('submit', function(e) {
                e.preventDefault();

                const linkVal = document.getElementById('import-playlist-link').value.trim();

                // Validação de link de playlist (deve ter "list=")
                if (!linkVal.includes('list=')) {
                    document.getElementById('import-link-error').classList.remove('hidden');
                    return;
                }

                // UI: mostrar loading
                const btn = document.getElementById('btn-import-submit');
                const progressContainer = document.getElementById('import-progress-container');
                const progressBar = document.getElementById('import-progress-bar');

                btn.disabled = true;
                btn.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">sync</span> Importando...';
                progressContainer.classList.remove('hidden');

                // Animação de progresso indeterminado enquanto espera a API
                let fakeProgress = 5;
                const progressInterval = setInterval(() => {
                    if (fakeProgress < 85) {
                        fakeProgress += Math.random() * 8;
                        progressBar.style.width = Math.min(fakeProgress, 85) + '%';
                    }
                }, 600);

                const formData = new FormData(formImportar);

                fetch('php/importar_playlist_youtube_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    clearInterval(progressInterval);
                    progressBar.style.width = '100%';

                    if (data.sucesso) {
                        setTimeout(() => {
                            window.closeImportPlaylistModal();
                            // Toast com estatísticas
                            const msg = `"${data.nome}" importada! ${data.novas} novas músicas + ${data.ja_existiam} já existiam.`;
                            if (typeof window.mostrarToastFeedback === 'function') {
                                window.mostrarToastFeedback(msg, 'success');
                            }
                            // Navega direto para a playlist recém criada
                            window.navegarSPA(`wavify_playlists.php?id=${data.playlist_id}`);
                        }, 400);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">download</span> Importar Playlist';
                        progressContainer.classList.add('hidden');
                        progressBar.style.width = '0%';
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback(data.erro || 'Erro ao importar playlist.', 'error');
                        }
                    }
                })
                .catch(err => {
                    clearInterval(progressInterval);
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">download</span> Importar Playlist';
                    progressContainer.classList.add('hidden');
                    progressBar.style.width = '0%';
                    console.error(err);
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Erro de conexão ao importar.', 'error');
                    }
                });
            });
        }
    }

    // Form submissions
    const setupSubmitHandlers = () => {
        const formCriar = document.getElementById('form-criar-playlist');
        if (formCriar) {
            formCriar.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(formCriar);
                
                fetch('php/criar_playlist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        window.closeCreateModal();
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback(`Playlist "${data.nome}" criada com sucesso!`, 'success');
                        }
                        // Open details of the new playlist
                        navegarSPA(`wavify_playlists.php?id=${data.id}`);
                    } else {
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback(data.erro || 'Erro ao criar playlist.', 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Erro de conexão ao criar playlist.', 'error');
                    }
                });
            };
        }

        const formEditar = document.getElementById('form-editar-playlist');
        if (formEditar) {
            formEditar.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(formEditar);
                
                fetch('php/editar_playlist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        window.closeEditModal();
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback('Alterações salvas com sucesso!', 'success');
                        }
                        // Reload the current page to reflect changes
                        navegarSPA(window.location.href);
                    } else {
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback(data.erro || 'Erro ao salvar alterações.', 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Erro de conexão ao salvar alterações.', 'error');
                    }
                });
            };
        }

        const formYoutubePlaylist = document.getElementById('form-youtube-playlist');
        if (formYoutubePlaylist) {
            formYoutubePlaylist.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(formYoutubePlaylist);
                const play_id = formData.get('playlist_id');
                
                // We first submit to adicionar_musica.php to save in library.
                // Wait! adicionar_musica.php redirects back by header Location, which causes SPA page loads.
                // Let's create a custom AJAX handling or request to add_musica.
                // Alternatively, we can let the backend register it and then associate it.
                // Let's make an AJAX endpoint for saving YouTube music and adding it directly to playlist.
                // We will create php/adicionar_musica_ajax.php, and call it. It's much cleaner!
                const link = document.getElementById('youtube_link_playlist').value;
                const title = document.getElementById('youtube_title_playlist').value;
                const artist = document.getElementById('youtube_artist_playlist').value;
                
                const saveForm = new FormData();
                saveForm.append('youtube_link', link);
                saveForm.append('titulo', title);
                saveForm.append('artista', artist);
                saveForm.append('playlist_id', play_id);
                
                fetch('php/adicionar_musica_ajax.php', {
                    method: 'POST',
                    body: saveForm
                })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        formYoutubePlaylist.reset();
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback('Música salva e adicionada à playlist!', 'success');
                        }
                        navegarSPA(window.location.href);
                    } else {
                        if (typeof window.mostrarToastFeedback === 'function') {
                            window.mostrarToastFeedback(data.erro || 'Erro ao adicionar música.', 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Erro de conexão.', 'error');
                    }
                });
            };
        }

        // Library live filtering
        const libSearchInput = document.getElementById('search-library-songs');
        if (libSearchInput) {
            libSearchInput.addEventListener('input', function() {
                const term = libSearchInput.value.trim().toLowerCase();
                const rows = document.querySelectorAll('.library-song-row');
                
                rows.forEach(row => {
                    const title = row.getAttribute('data-title') || '';
                    const artist = row.getAttribute('data-artist') || '';
                    
                    if (title.includes(term) || artist.includes(term)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            });
        }

        // Modal search live filtering
        const modalSearchInput = document.getElementById('search-modal-songs');
        if (modalSearchInput) {
            modalSearchInput.addEventListener('input', function() {
                const term = modalSearchInput.value.trim().toLowerCase();
                const rows = document.querySelectorAll('#modal-songs-container .modal-song-row');
                
                rows.forEach(row => {
                    const title = row.getAttribute('data-title') || '';
                    const artist = row.getAttribute('data-artist') || '';
                    
                    if (title.includes(term) || artist.includes(term)) {
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Setup YouTube link dynamic autofill for playlist page too
        const linkInput = document.getElementById('youtube_link_playlist');
        const titleInput = document.getElementById('youtube_title_playlist');
        const artistInput = document.getElementById('youtube_artist_playlist');

        if (linkInput && titleInput && artistInput) {
            linkInput.addEventListener('input', () => {
                const url = linkInput.value.trim();
                if (url.includes('youtube.com') || url.includes('youtu.be')) {
                    linkInput.classList.add('border-primary/60');
                    
                    fetch(`php/obter_metadados_youtube.php?url=${encodeURIComponent(url)}`)
                        .then(res => res.json())
                        .then(data => {
                            linkInput.classList.remove('border-primary/60');
                            if (data.titulo) {
                                titleInput.value = data.titulo;
                            }
                            if (data.artista) {
                                artistInput.value = data.artista;
                            }
                        })
                        .catch(err => {
                            linkInput.classList.remove('border-primary/60');
                            console.error("Erro ao buscar metadados: ", err);
                        });
                }
            });
        }
    };

    // Actions on playlist details
    window.removerMusicaDaPlaylist = function(playlistId, musicaId, titulo) {
        const formData = new FormData();
        formData.append('playlist_id', playlistId);
        formData.append('musica_id', musicaId);
        
        fetch('php/remover_musica_playlist.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback(`"${titulo}" removida da playlist.`, 'success');
                }
                
                // Animated row deletion
                const row = document.getElementById('playlist-track-item-' + musicaId);
                if (row) {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        // Refresh the page structure via SPA
                        navegarSPA(window.location.href);
                    }, 400);
                } else {
                    navegarSPA(window.location.href);
                }
            } else {
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback(data.erro || 'Erro ao remover música.', 'error');
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof window.mostrarToastFeedback === 'function') {
                window.mostrarToastFeedback('Erro de conexão.', 'error');
            }
        });
    };

    window.adicionarMusicaSalva = function(playlistId, musicaId, titulo, buttonElement) {
        const formData = new FormData();
        formData.append('playlist_id', playlistId);
        formData.append('musica_id', musicaId);
        
        if (buttonElement) {
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<span class="material-symbols-outlined text-[14px] animate-spin">sync</span> Salvando';
        }

        fetch('php/adicionar_musica_playlist.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback(`"${titulo}" adicionada com sucesso!`, 'success');
                }
                
                // Animate removal of the added track row from library list
                const row = buttonElement.closest('.library-song-row');
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Check if list is empty
                        const container = document.getElementById('library-songs-container');
                        if (container && container.querySelectorAll('.library-song-row').length === 0) {
                            container.innerHTML = '<div class="text-on-surface-variant text-center py-8 text-sm">Nenhuma música disponível para adicionar.</div>';
                        }
                    }, 300);
                }
                
                // Refresh list of tracks inside playlist
                setTimeout(() => {
                    navegarSPA(window.location.href);
                }, 400);
            } else {
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = '<span class="material-symbols-outlined text-[14px]">add</span> Adicionar';
                }
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback(data.erro || 'Erro ao adicionar música.', 'error');
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (buttonElement) {
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<span class="material-symbols-outlined text-[14px]">add</span> Adicionar';
            }
            if (typeof window.mostrarToastFeedback === 'function') {
                window.mostrarToastFeedback('Erro de conexão.', 'error');
            }
        });
    };

    // Playback functions specifically bound to this page
    window.ouvirTudoPlaylist = function() {
        if (window.wavifyMusicas && window.wavifyMusicas.length > 0) {
            // Load and play the first index
            tocarMusicaPorIndice(0);
            if (typeof window.mostrarToastFeedback === 'function') {
                window.mostrarToastFeedback('Iniciando reprodução da playlist...', 'success');
            }
        }
    };

    window.ouvirTudoDaPlaylistCard = function(playlistId) {
        // Fetch playlist songs and play them
        fetch(`php/obter_musicas_playlist.php?playlist_id=${playlistId}`)
            .then(res => res.json())
            .then(data => {
                if (data.sucesso && data.musicas && data.musicas.length > 0) {
                    // Update global music array to play from
                    window.wavifyMusicas = data.musicas;
                    if (typeof playlistInicial !== 'undefined') {
                        playlistInicial.length = 0;
                        data.musicas.forEach(t => playlistInicial.push(t));
                    }
                    tocarMusicaPorIndice(0);
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Tocando playlist...', 'success');
                    }
                } else {
                    if (typeof window.mostrarToastFeedback === 'function') {
                        window.mostrarToastFeedback('Esta playlist não possui músicas.', 'error');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof window.mostrarToastFeedback === 'function') {
                    window.mostrarToastFeedback('Erro de conexão ao reproduzir playlist.', 'error');
                }
            });
    };

    // Initialize all submit handlers and logic
    setupSubmitHandlers();
})();
</script>

<?php
render_page_end($usuario, $player_tracks);
?>
