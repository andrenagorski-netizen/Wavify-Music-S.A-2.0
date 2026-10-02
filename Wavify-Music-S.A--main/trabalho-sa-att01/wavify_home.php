<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

// Get user's own musics
$musicas = user_musics($usuario_id);

// Get user's recently played musics
global $conexao;
$sql_recentes = "
SELECT m.*
FROM reproducoes_recentes r
INNER JOIN musicas m ON m.id = r.musica_id
WHERE r.usuario_id = '$usuario_id'
AND m.usuario_id = '$usuario_id'
ORDER BY r.tocada_em DESC
LIMIT 10
";
$resultado_recentes = mysqli_query($conexao, $sql_recentes);
$musicas_recentes = [];
while($musica_recente = mysqli_fetch_assoc($resultado_recentes)){
    $musicas_recentes[] = $musica_recente;
}

$capa_padrao = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAE8qW-12I3R5AbdBkyMrbYsfIWcnx5Hfw7KtsNTJPyMHUVYOT7iFXhM2dv-zHwAK_Skvb-MKyyDDkSYTrF7Cs-qwLmp2_Xe-RShetIuoBXsmXdFXlzOC-aqEMkwddXuICfYFY-_gYJcYek11EVxyz8Wp9q04wfaJa6jHxjspngBcOoZWnSlW12xjgXM5VwJ6v0mv7UqZwFbpgoTUbogyiccDWk-UaPs-kncYghGeyMUvFDowiUoxAKdFMOuHEYT0GMaVSe3kjDCZM';
$playlist_player = count($musicas_recentes) > 0 ? $musicas_recentes : $musicas;
$musica_destaque = count($playlist_player) > 0 ? $playlist_player[0] : null;

// Start page rendering
render_page_start('WAVIFY - Home', 'wavify_home.php', $usuario, $playlist_player);
?>

<!-- Featured Hero Section -->
<section class="mb-12 pt-4 md:pt-0">
<div class="relative w-full h-[400px] md:h-[500px] rounded-2xl overflow-hidden group">
<img id="hero-capa-fundo" alt="Capa da musica em destaque" class="absolute inset-0 w-full h-full object-cover scale-105 opacity-75 group-hover:scale-110 transition-transform duration-700" src="<?= htmlspecialchars($musica_destaque ? $musica_destaque['thumbnail'] : $capa_padrao) ?>"/>
<div class="absolute inset-0 bg-gradient-to-t from-background via-background/45 to-background/10"></div>
<div class="absolute inset-0 bg-black/25"></div>
<div class="absolute inset-0 p-8 md:p-12 flex flex-col justify-end">
<div class="flex items-end justify-between gap-6">
<div class="flex-1 max-w-3xl">
<span id="hero-status" class="inline-block px-3 py-1 bg-white/10 backdrop-blur-md rounded-full font-label-md text-label-md text-primary mb-4 border border-white/10 uppercase tracking-widest"><?= $musica_destaque ? 'Pronta para tocar' : 'Sem musica' ?></span>
<h2 id="hero-titulo" class="font-headline-xl text-headline-xl text-on-surface mb-2 tracking-tight group-hover:text-primary transition-colors duration-500 truncate"><?= htmlspecialchars($musica_destaque ? $musica_destaque['titulo'] : 'Nenhuma musica') ?></h2>
<p class="font-headline-sm text-headline-sm text-on-surface-variant flex items-center gap-2 min-w-0">
<span id="hero-artista" class="truncate"><?= htmlspecialchars($musica_destaque ? $musica_destaque['artista'] : 'Toque algo na biblioteca') ?></span>
                                <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings: 'FILL' 1;">verified</span>
</p>
</div>
<button onclick="playPauseMusica()" class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-primary to-primary-container rounded-full flex items-center justify-center shrink-0 hover:scale-110 transition-transform duration-300 shadow-[0_0_30px_rgba(124,58,237,0.5)] group-hover:shadow-[0_0_50px_rgba(124,58,237,0.8)] relative" aria-label="Pausar ou tocar musica em destaque">
<span class="absolute inset-0 rounded-full border border-white/30 animate-pulse-slow"></span>
<span id="hero-icone-play-pause" class="material-symbols-outlined text-4xl text-on-primary-fixed ml-2" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
</button>
</div>
<!-- Decorative Waveform Simulation -->
<div id="hero-waveform" class="mt-8 flex items-end gap-1 h-12 opacity-40 w-full max-w-md transition-opacity">
<div class="w-1 bg-primary rounded-t-sm h-[30%] animate-[pulse_1s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[60%] animate-[pulse_1.2s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[40%] animate-[pulse_0.8s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[90%] animate-[pulse_1.5s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[50%] animate-[pulse_0.9s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[70%] animate-[pulse_1.1s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[20%] animate-[pulse_0.7s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[80%] animate-[pulse_1.3s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[45%] animate-[pulse_1s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[30%] animate-[pulse_1.2s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[100%] animate-[pulse_1.4s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[60%] animate-[pulse_0.8s_ease-in-out_infinite_alternate]"></div>
<div class="w-1 bg-primary rounded-t-sm h-[40%] animate-[pulse_1.1s_ease-in-out_infinite_alternate]"></div>
</div>
</div>
</div>
</section>

<!-- Recently Played -->
<section class="mb-16">
<div class="flex items-center justify-between mb-6">
<h3 class="font-headline-md text-headline-md text-on-surface">Tocadas Recentemente</h3>
<a class="font-label-md text-label-md text-primary hover:text-primary-fixed transition-colors" href="wavify_my_library.php">Ver Tudo</a>
</div>
<div class="relative group/carousel">
    <!-- Botão de Voltar (Esquerda) -->
    <button 
        id="btn-scroll-left"
        style="display: none;"
        onclick="scrollCarrossel('recentes-scroll-container', 'left')" 
        class="absolute left-2 top-[38%] -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-surface-container/80 border border-white/10 text-on-surface hover:text-primary flex items-center justify-center backdrop-blur-md opacity-0 group-hover/carousel:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95 shadow-xl pointer-events-auto"
        title="Voltar"
    >
        <span class="material-symbols-outlined text-[24px]">chevron_left</span>
    </button>

    <!-- Botão de Avançar (Direita) -->
    <button 
        id="btn-scroll-right"
        onclick="scrollCarrossel('recentes-scroll-container', 'right')" 
        class="absolute right-2 top-[38%] -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-surface-container/80 border border-white/10 text-on-surface hover:text-primary flex items-center justify-center backdrop-blur-md opacity-0 group-hover/carousel:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95 shadow-xl pointer-events-auto"
        title="Avançar"
    >
        <span class="material-symbols-outlined text-[24px]">chevron_right</span>
    </button>

    <div id="recentes-scroll-container" class="flex overflow-x-auto gap-6 pb-6 hide-scrollbar -mx-4 px-4 md:mx-0 md:px-0">
    <?php if(count($musicas_recentes) > 0): ?>
    <?php foreach($musicas_recentes as $indice_recente => $musica_recente): ?>
    <div class="w-40 md:w-48 shrink-0 group cursor-pointer" data-search-item onclick="tocarMusicaPorIndice(<?= $indice_recente ?>)">
    <div class="relative w-full aspect-square rounded-xl overflow-hidden mb-4 border border-white/5 group-hover:border-primary/50 transition-colors duration-300">
    <img alt="Capa de <?= htmlspecialchars($musica_recente['titulo']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?= htmlspecialchars($musica_recente['thumbnail']) ?>"/>
    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
    <span class="w-12 h-12 bg-primary rounded-full flex items-center justify-center shadow-[0_0_20px_rgba(124,58,237,0.6)] translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
    <span class="material-symbols-outlined text-on-primary-fixed" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
    </span>
    </div>
    </div>
    <h4 class="font-label-lg text-label-lg text-on-surface truncate"><?= htmlspecialchars($musica_recente['titulo']) ?></h4>
    <p class="font-body-sm text-body-sm text-on-surface-variant truncate mt-1"><?= htmlspecialchars($musica_recente['artista']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="w-full rounded-xl border border-white/5 bg-surface-container-low/50 px-6 py-8 text-on-surface-variant">
    <p class="font-body-md text-body-md">Nenhuma música reproduzida ainda.</p>
    </div>
    <?php endif; ?>
    </div>
</div>
</section>

<?php
render_page_end($usuario, $playlist_player);
?>

<script>
// Manutenção 04 — Seta de navegação das músicas (SPA Fix)
(function() {
    const initArrows = () => {
        const container = document.getElementById('recentes-scroll-container');
        const btnLeft = document.getElementById('btn-scroll-left');
        const btnRight = document.getElementById('btn-scroll-right');

        if (!container || !btnLeft || !btnRight) return;

        const updateArrows = () => {
            // Ocultar esquerda se no início
            if (container.scrollLeft <= 2) {
                btnLeft.style.display = 'none';
            } else {
                btnLeft.style.display = 'flex';
            }

            // Ocultar direita se no fim
            if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 2) {
                btnRight.style.display = 'none';
            } else {
                btnRight.style.display = 'flex';
            }
        };

        container.addEventListener('scroll', updateArrows);
        window.addEventListener('resize', updateArrows);
        
        // Timeout para garantir que as divs foram carregadas e o scrollWidth calculado
        setTimeout(updateArrows, 150); 
    };

    // Funciona no primeiro load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initArrows);
    } else {
        // Funciona ao ser injetado via roteador SPA (main.js)
        initArrows();
    }
})();
</script>
