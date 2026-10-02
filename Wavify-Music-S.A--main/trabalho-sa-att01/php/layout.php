<?php
require_once __DIR__ . '/app.php';

function render_head(string $title = 'WAVIFY'): void { ?>
<!DOCTYPE html>
<html class="dark" lang="pt-br">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($title) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: {
    colors: {
      background: '#131318', surface: '#131318', 'surface-container': '#1f1f25', 'surface-container-low': '#1b1b20', 'surface-container-high': '#2a292f', 'surface-container-highest': '#35343a', 'surface-bright': '#39383e', primary: '#d2bbff', 'primary-container': '#7c3aed', secondary: '#ffb95f', error: '#ffb4ab', 'error-container': '#93000a', 'on-surface': '#e4e1e9', 'on-background': '#e4e1e9', 'on-surface-variant': '#ccc3d8', 'on-primary-fixed': '#25005a', 'on-primary-container': '#ede0ff'
    },
    fontFamily: { sans: ['DM Sans'], 'body-md': ['DM Sans'], 'label-lg': ['DM Sans'], 'headline-md': ['Syne'], 'headline-xl': ['Syne'], 'headline-sm': ['Syne'] }
  }}
};
</script>
<script>
(function() {
    if (localStorage.getItem('wavify_menu_collapsed') === 'true') {
        document.documentElement.classList.add('menu-collapsed');
    }
    window.scrollCarrossel = function(containerId, direcao) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const scrollAmount = container.clientWidth * 0.7;
        container.scrollBy({
            left: direcao === 'left' ? -scrollAmount : scrollAmount,
            behavior: 'smooth'
        });
    };
})();
</script>
<style>
body { font-family: 'DM Sans', sans-serif; background: radial-gradient(circle at top left, rgba(124,58,237,.18), transparent 32%), #0a0a0f; }
.font-headline-md,.font-headline-xl,.font-headline-sm { font-family: 'Syne', sans-serif; }
.page-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.1;
    color: #e4e1e9;
}
.wavify-card { background: rgba(31,31,37,.62); border: 1px solid rgba(255,255,255,.07); }

/* Sidebar Responsive Styles */
@media (min-width: 768px) {
    #app-sidebar { width: 260px; padding: 1.5rem; transition: width 0.25s ease, padding 0.25s ease; }
    body { padding-left: 260px; transition: padding-left 0.25s ease; }
    #topbar { left: 260px; transition: left 0.25s ease; }
    
    html.menu-collapsed #app-sidebar { width: 80px; padding: 1.5rem 0.5rem; align-items: center; }
    html.menu-collapsed body { padding-left: 80px; }
    html.menu-collapsed #topbar { left: 80px; }

    html.menu-collapsed .sidebar-text,
    html.menu-collapsed .sidebar-logo { display: none; }
    
    html.menu-collapsed #app-sidebar nav a, 
    html.menu-collapsed #app-sidebar .sidebar-bottom a { 
        justify-content: center; padding-left: 0; padding-right: 0; width: 48px; height: 48px;
    }
    
    .sidebar-tooltip {
        position: absolute; left: 100%; top: 50%; transform: translateY(-50%);
        margin-left: 12px; padding: 6px 12px; background: #2a292f;
        color: #e4e1e9; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; font-size: 14px; white-space: nowrap; z-index: 100;
        opacity: 0; pointer-events: none; transition: opacity 0.2s;
    }
    html.menu-collapsed #app-sidebar a:hover .sidebar-tooltip { opacity: 1; }
}
@media (max-width: 767px) {
    #app-sidebar { width: 260px; padding: 1.5rem; }
    .sidebar-tooltip { display: none; }
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
/* Manutencao 05 - Remover bordas pretas em capas do Youtube cortando pelas margens */
img[src*="i.ytimg.com"] {
    object-fit: cover;
    transform: scale(1.35);
}
.group:hover img[src*="i.ytimg.com"] {
    transform: scale(1.45);
}
</style>
</head>
<?php }

function nav_items(): array {
    return [
        ['wavify_home.php', 'home', 'Início'],
        ['wavify_discover.php', 'explore', 'Descobrir'],
        ['wavify_my_library.php', 'library_music', 'Minha Biblioteca'],
        ['wavify_playlists.php', 'playlist_play', 'Playlists'],
        ['wavify_upload.php', 'cloud_upload', 'Upload'],
    ];
}

function render_sidebar(string $active): void { ?>
<aside id="app-sidebar" class="fixed left-0 top-0 h-full bg-surface/95 backdrop-blur-xl border-r border-white/10 flex flex-col gap-8 z-50 transition-transform duration-250 ease-in-out -translate-x-full md:translate-x-0 overflow-visible">
  <div class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-container to-secondary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-white">graphic_eq</span></div>
    <div class="sidebar-logo min-w-0"><h1 class="font-headline-md text-2xl font-bold text-primary">WAVIFY</h1><p class="text-xs text-on-surface-variant uppercase tracking-widest truncate">Áudio Premium</p></div>
  </div>
  <nav class="flex flex-col gap-2 flex-1 overflow-visible">
    <?php foreach (nav_items() as [$href, $icon, $label]): $on = $active === $href; ?>
      <a href="<?= $href ?>" class="relative flex items-center gap-4 px-4 py-3 rounded-full <?= $on ? 'bg-gradient-to-r from-primary to-secondary text-on-primary-fixed' : 'text-on-surface-variant hover:text-on-surface hover:bg-white/5' ?> transition-all font-bold">
        <span class="material-symbols-outlined shrink-0"<?= $on ? ' style="font-variation-settings: \'FILL\' 1;"' : '' ?>><?= $icon ?></span>
        <span class="sidebar-text truncate"><?= $label ?></span>
        <span class="sidebar-tooltip shadow-lg"><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="border-t border-white/10 pt-5 flex flex-col gap-2 sidebar-bottom overflow-visible">
    <a href="wavify_profile.php" class="relative flex items-center gap-4 px-4 py-3 rounded-full <?= $active === 'wavify_profile.php' ? 'bg-white/5 text-primary' : 'text-on-surface-variant hover:text-on-surface hover:bg-white/5' ?> transition-all font-bold">
      <span class="material-symbols-outlined shrink-0">settings</span>
      <span class="sidebar-text truncate">Perfil</span>
      <span class="sidebar-tooltip shadow-lg">Perfil</span>
    </a>
    <a href="php/logout.php" class="relative flex items-center gap-4 px-4 py-3 rounded-full text-on-surface-variant hover:text-error hover:bg-error/10 transition-all font-bold">
      <span class="material-symbols-outlined shrink-0">logout</span>
      <span class="sidebar-text truncate">Sair</span>
      <span class="sidebar-tooltip shadow-lg">Sair</span>
    </a>
  </div>
</aside>
<?php }

function render_topbar(array $user): void { ?>
<header class="fixed top-0 left-0 right-0 h-20 z-40 bg-background/55 backdrop-blur-xl border-b border-white/5 flex items-center gap-4 px-4 md:px-10 transition-all duration-250 ease-in-out" id="topbar">
  <button id="mobile-menu-button" class="w-11 h-11 rounded-full bg-white/5 border border-white/10 flex items-center justify-center z-50 transition-all duration-300 hover:bg-white/10 hover:scale-105 active:scale-95" aria-label="Alternar menu">
    <div class="w-5 h-3.5 relative flex flex-col justify-between">
      <span class="w-full h-[2px] bg-on-surface rounded-full transition-all duration-300 ease-in-out origin-center" id="bar1"></span>
      <span class="w-full h-[2px] bg-on-surface rounded-full transition-all duration-300 ease-in-out" id="bar2"></span>
      <span class="w-full h-[2px] bg-on-surface rounded-full transition-all duration-300 ease-in-out origin-center" id="bar3"></span>
    </div>
  </button>
  <div class="relative flex-1">
    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
    <input type="search" class="w-full bg-white/5 border border-white/10 rounded-full py-3 pl-12 pr-4 text-on-surface focus:ring-2 focus:ring-primary/40" placeholder="Buscar faixas, artistas, álbuns..." />
  </div>
  <div class="hidden sm:flex items-center gap-3">
    <div class="relative group">
        <button id="btn-notifications" class="w-11 h-11 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-white/10 transition-colors" aria-label="Notificações">
          <span class="material-symbols-outlined">notifications</span>
          <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-primary rounded-full border-2 border-surface-container animate-pulse"></span>
        </button>
        <!-- Notificações Dropdown -->
        <div id="dropdown-notifications" class="absolute right-0 mt-2 w-72 bg-surface-container/95 border border-white/10 rounded-2xl shadow-2xl z-[100] backdrop-blur-xl hidden transform origin-top-right transition-all duration-200 opacity-0 scale-95 flex-col">
            <div class="px-4 py-3 border-b border-white/10 font-bold text-on-surface">Notificações</div>
            <div class="flex-1 max-h-64 overflow-y-auto p-2">
                <div class="px-3 py-2 hover:bg-white/5 rounded-lg cursor-pointer mb-1 transition-colors">
                    <p class="text-sm font-bold text-on-surface">Bem-vindo(a) ao Wavify!</p>
                    <p class="text-xs text-on-surface-variant">Sua biblioteca de música está pronta.</p>
                </div>
                <div class="px-3 py-2 hover:bg-white/5 rounded-lg cursor-pointer transition-colors">
                    <p class="text-sm font-bold text-on-surface">Nova atualização</p>
                    <p class="text-xs text-on-surface-variant">Confira as playlists em destaque.</p>
                </div>
            </div>
            <div class="px-4 py-2 text-center border-t border-white/10">
                <a href="#" class="text-xs text-primary hover:underline font-bold">Marcar todas como lidas</a>
            </div>
        </div>
    </div>
    <a href="wavify_upload.php" class="px-5 py-2 rounded-full bg-gradient-to-r from-primary to-secondary text-on-primary-fixed font-bold">Upload</a>
    <a href="wavify_profile.php" class="w-10 h-10 rounded-full overflow-hidden border border-primary/50"><img id="header-profile-img" class="w-full h-full object-cover" alt="Perfil" src="<?= htmlspecialchars($_SESSION['wavify_user_img'] ?? 'https://i.pravatar.cc/120?img=12') ?>"/></a>
  </div>
</header>
<?php }

function render_player(array $tracks): void {
    $first = $tracks[0] ?? null;
    $cover = $first['thumbnail'] ?? 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg';
    $title = $first['titulo'] ?? 'Nenhuma música';
    $artist = $first['artista'] ?? 'Adicione uma música'; ?>
<footer class="fixed bottom-0 left-0 w-full h-[96px] z-50 bg-surface-container/95 backdrop-blur-2xl border-t border-white/10 flex items-center justify-between px-4 md:px-8">
  <div class="flex items-center gap-4 w-1/3 min-w-[180px]">
    <img id="player-capa" class="w-14 h-14 rounded-md object-cover hidden sm:block" src="<?= htmlspecialchars($cover) ?>" alt="Capa"/>
    <div class="min-w-0"><a id="player-titulo" class="block font-bold text-on-surface truncate" href="#"><?= htmlspecialchars($title) ?></a><a id="player-artista" class="block text-sm text-on-surface-variant truncate" href="#"><?= htmlspecialchars($artist) ?></a></div>
  </div>
  <div class="flex flex-col items-center justify-center w-1/3 max-w-md">
    <div class="flex items-center gap-5 mb-2">
      <button id="shuffle-button" onclick="alternarAleatorio()" class="hidden sm:block text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">shuffle</span></button>
      <button onclick="musicaAnterior()" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">skip_previous</span></button>
      <button onclick="playPauseMusica()" class="w-12 h-12 rounded-full bg-primary text-on-primary-fixed flex items-center justify-center shadow-[0_0_20px_rgba(124,58,237,.5)]"><span id="icone-play-pause" class="material-symbols-outlined text-3xl">play_arrow</span></button>
      <button onclick="proximaMusica()" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">skip_next</span></button>
      <button id="repeat-button" onclick="alternarRepetir()" class="hidden sm:block text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">repeat</span></button>
    </div>
    <div class="hidden md:flex w-full items-center gap-2"><span id="tempo-atual" class="text-[10px] text-on-surface-variant">0:00</span><div id="barra-progresso" onclick="alterarProgresso(event)" class="h-1 flex-1 bg-white/10 rounded-full relative cursor-pointer"><div id="progresso-musica" class="absolute top-0 left-0 h-full w-0 rounded-full bg-gradient-to-r from-primary to-secondary"></div><div id="controle-progresso" class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-white"></div></div><span id="tempo-duracao" class="text-[10px] text-on-surface-variant">0:00</span></div>
  </div>
  <div class="hidden sm:flex items-center justify-end gap-3 w-1/3"><button onclick="alternarMudo()" class="text-on-surface-variant"><span id="icone-volume" class="material-symbols-outlined">volume_up</span></button><div id="barra-volume" onclick="alterarVolume(event)" class="w-24 h-1 bg-white/10 rounded-full relative cursor-pointer"><div id="nivel-volume" class="h-full w-full rounded-full bg-on-surface-variant"></div><div id="controle-volume" class="absolute left-full top-1/2 -translate-y-1/2 -translate-x-1/2 w-3 h-3 bg-white rounded-full"></div></div></div>
</footer>
<div id="youtube-player" style="position:fixed;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;"></div>
<?php }

function render_page_start(string $title, string $active, array $user, array $tracks): void {
    render_head($title); ?>
<body class="bg-background text-on-background min-h-screen pb-[120px] transition-all duration-250 ease-in-out overflow-x-hidden">
<?php render_sidebar($active); render_topbar($user); ?>
<div id="mobile-menu-backdrop" class="hidden fixed inset-0 z-40 bg-black/60 md:hidden"></div>
<main class="pt-24 px-4 md:px-10 min-h-screen">
<?php }

function render_page_end(array $user, array $tracks): void { ?>
</main>
<?php render_player($tracks); ?>
<script>
window.wavifyUsuario = <?= json_encode(['nome' => $user['usuario'] ?? '', 'email' => $user['email'] ?? ''], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.wavifyMusicas = <?= json_tracks($tracks) ?>;

// Manutencao 02: Dropdown de notificacoes
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btn-notifications');
    const dropdown = document.getElementById('dropdown-notifications');
    if(btn && dropdown) {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !dropdown.classList.contains('hidden');
            if(isOpen) {
                dropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => dropdown.classList.add('hidden'), 200);
            } else {
                dropdown.classList.remove('hidden', 'opacity-0', 'scale-95');
                dropdown.classList.add('flex');
            }
        });
        document.addEventListener('click', (e) => {
            if(!dropdown.contains(e.target) && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => dropdown.classList.add('hidden'), 200);
            }
        });
    }
});
</script>
<script src="player.js"></script>
<script src="https://www.youtube.com/iframe_api"></script>
<script src="main.js"></script>
</body></html>
<?php }
?>
