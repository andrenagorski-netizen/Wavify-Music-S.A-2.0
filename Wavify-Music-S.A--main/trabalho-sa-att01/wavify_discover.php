<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

// Fetch user's own musics for initial player state
$musicas = user_musics($usuario_id);

// Fetch top global charts tracks (simply user's own tracks ordered by something, or recent tracks)
global $conexao;
$resultado_charts = mysqli_query($conexao, "SELECT * FROM musicas WHERE usuario_id = '$usuario_id' ORDER BY data_adicao DESC LIMIT 5");
$charts_tracks = [];
while ($row = mysqli_fetch_assoc($resultado_charts)) {
    $charts_tracks[] = $row;
}

// Start page rendering
render_page_start('WAVIFY - Descobrir', 'wavify_discover.php', $usuario, $musicas);
?>

<header class="mb-12">
<h2 class="page-title text-transparent bg-clip-text bg-gradient-to-r from-white to-on-surface-variant drop-shadow-lg">Descubra Novos Sons</h2>
<p class="font-body-lg text-body-lg text-on-surface-variant mt-2 max-w-2xl">Mergulhe nas frequências que moldam o amanhã. Suas músicas filtradas por gênero e humor.</p>
</header>

<!-- Section 1: Genres Bento -->
<section class="mb-16">
<div class="flex items-center justify-between mb-6">
<h3 class="font-headline-md text-headline-md text-on-surface">Gêneros</h3>
</div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 auto-rows-[160px]">
    
    <div class="col-span-2 row-span-2 rounded-xl overflow-hidden relative group cursor-pointer border border-white/5 hover:border-primary/50 transition-all duration-300" data-genre-card="Electronic">
        <div class="absolute inset-0 bg-gradient-to-t from-background via-background/50 to-transparent z-10"></div>
        <img alt="Electronic music concert" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBlHyeysQTrXjxc8exnAXnGRRt43t4GLhYnXFUeJ265K8KUiQEs87QmwIpqkjwWn8GdBhg49TlTJSbMIV6eqnL7xhpL8-NuhgjVWeXMoR5GELxLz6LhHaXpfBn54D_Ynpdcn-kXOCBqW-6FJKZLFhIm6ALlAVeM6Dbqxd_Wj-5SJp95AmB8IQilY97fgQR8yCqKy1B2y-PE-eKI0CmhMkgw7dTZ7gBteKTRRi8M_jpSeRZVkjMz1mCzGo-oRQmF72Br7umioNtjcIY"/>
        <div class="absolute bottom-6 left-6 z-20">
            <h4 class="font-headline-lg text-headline-lg text-white font-bold tracking-tight">Electronic</h4>
            <p class="font-body-sm text-body-sm text-primary mt-1">Live sets &amp; club cuts</p>
        </div>
    </div>

    <div class="rounded-xl overflow-hidden relative group cursor-pointer border border-white/5 hover:border-secondary/50 transition-all duration-300" data-genre-card="Hip Hop">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
        <img alt="Hip-Hop artist performing" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 filter grayscale group-hover:grayscale-0" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAsb4Dna_aq9dk-B3dK_c_zDiiT82TdLn1tLwC8PvCIZfPZqxebmg5e1Co7-aqmz8LpzhktyrujEqJ9h3ISKQtkEKME7WrfXdgxu6Dl323hJq4jr5e7vnAtfq1Ca5k4gyBxpKAvq9gRFgaR94rEAYpw6mE5yec0fIfDJwI4EnoyfrnYRKx31PIOgSyVG9ZadBSWUW2V0ut24bJsLs_XwHiRpZbal7MpSEyZQ2Q3VsvbrWn0aI920AWI3EAnx0Kxr5dh4fhELmj9pvc"/>
        <div class="absolute bottom-4 left-4 z-20">
            <h4 class="font-headline-sm text-headline-sm text-white">Hip-Hop</h4>
        </div>
    </div>

    <div class="rounded-xl overflow-hidden relative group cursor-pointer border border-white/5 hover:border-error/50 transition-all duration-300" data-genre-card="Pop">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
        <img alt="Pop music aesthetics" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA5XQLb1a1v-6K9S7va6Y9Cq99QvfS5Az9ps7jz0KBUOLws_oV-LjS_o49ER-k4i_nbFlNJsSbFuqQh-AsuA4OBF9Sxaq070BRIfsVxG0mxz7snFhziKniOSgZe2lssLBHqonjB2sZslKODA0BW8U3RCTMPuxEyFDq8LSu-urLwtu-jp4gqJ3Nf3XqqDP2fNPFWEydG5yyetSFnNVlg0KOBL4cyzmo1tAmHBmO7IEg9_UHBD9tq55wtoVrp9z3CLJpmUjAx_BxQZHA"/>
        <div class="absolute bottom-4 left-4 z-20">
            <h4 class="font-headline-sm text-headline-sm text-white">Pop</h4>
        </div>
    </div>

    <div class="col-span-2 md:col-span-1 rounded-xl overflow-hidden relative group cursor-pointer border border-white/5 hover:border-tertiary/50 transition-all duration-300" data-genre-card="R&B">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
        <img alt="R&amp;B mood lighting" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBDStnrOmfw-xQY3qcn1Yjmo1NB98ouim2S9HZarjgsdD6c_ollyEZRjaqr9f3YS3G-yz3dw95vtdlkw8jblnffCxnEv6wvwsk-rqhSHP_mPRO6pfPN0xjUrHabLZDd7UxyPAGVb7gaODM5doi9L7a4MwFnJ8fPfkJ2AK6v8_IJNN3Bv8JiTy_z-Oo-Llwxg0FugRXcOz5o58_cHN_z97hopS-L5BBMUVY0Ul3m39aNOXXqNuWW6LLhWvFaKqQXZa2yHatbJarzVs4"/>
        <div class="absolute bottom-4 left-4 z-20">
            <h4 class="font-headline-sm text-headline-sm text-white">R&amp;B</h4>
        </div>
    </div>

    <div class="col-span-2 md:col-span-1 rounded-xl overflow-hidden relative group cursor-pointer border border-white/5 hover:border-primary-container/50 transition-all duration-300" data-genre-card="Rock">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
        <img alt="Rock guitar strings" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDKwkmfA-FA_Yv9kXOo0-FVJ401LTjHhsWf8mhwJL7aVMzzWxTU0AZO6hReUmQDhOqmH4Zd6YQb8y-J6y96xZnNkpAZHeycwGWJa_CZY6O3429iZjv9XACL0Xt_TKinMjQ8QzXMg5NW6Hs9Gkwg75BWRQ4KO0BJJ5wHne-MZ3TKj3w2ztJmxsRtX4ykDX-WByyx2WW5g6wteyYvPRENKabrvgchkTGevv4Y50GzZdY8naUvK5rv5zbTt3cq952r4DTUiuq4nY_OCdw"/>
        <div class="absolute bottom-4 left-4 z-20">
            <h4 class="font-headline-sm text-headline-sm text-white">Rock</h4>
        </div>
    </div>

</div>
</section>

<!-- Section 2: Global Charts List -->
<section class="mb-16">
<h3 class="font-headline-md text-headline-md text-on-surface mb-6">Paradas Globais</h3>
<div class="bg-surface-container-low/50 backdrop-blur-md rounded-2xl border border-white/5 p-4 md:p-6 shadow-xl">
<!-- Table Header -->
<div class="hidden md:grid grid-cols-[40px_minmax(200px,1fr)_150px_100px] gap-4 mb-4 px-4 text-on-surface-variant font-label-md text-label-md border-b border-white/10 pb-2">
<div class="text-center">#</div>
<div>Faixa</div>
<div>Gênero</div>
<div class="text-right">Ação</div>
</div>

<!-- List Items -->
<div class="flex flex-col gap-2">
<?php if (count($charts_tracks) > 0): ?>
<?php foreach($charts_tracks as $indice_chart => $chart_track): ?>
<div class="group flex items-center md:grid md:grid-cols-[40px_minmax(200px,1fr)_150px_100px] gap-4 p-3 md:px-4 rounded-xl hover:bg-white/5 hover:shadow-[0_0_20px_rgba(124,58,237,0.1)] transition-all cursor-pointer" onclick="tocarMusicaPorIndice(<?= $indice_chart ?>)">
    <div class="w-8 md:w-full text-center font-headline-sm text-headline-sm text-primary"><?= $indice_chart + 1 ?></div>
    <div class="flex items-center gap-4 flex-1 min-w-0">
        <div class="w-12 h-12 rounded-md overflow-hidden relative flex-shrink-0">
            <img alt="Capa" class="w-full h-full object-cover" src="<?= htmlspecialchars($chart_track['thumbnail']) ?>"/>
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
            </div>
        </div>
        <div class="truncate">
            <h5 class="font-body-md text-body-md text-on-surface truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($chart_track['titulo']) ?></h5>
            <p class="font-body-sm text-body-sm text-on-surface-variant truncate"><?= htmlspecialchars($chart_track['artista']) ?></p>
        </div>
    </div>
    <div class="hidden md:flex items-center text-on-surface-variant font-body-sm text-body-sm">
        <?= htmlspecialchars($chart_track['genero'] ?: 'Geral') ?>
    </div>
    <div class="hidden md:flex items-center justify-end text-on-surface-variant font-body-sm text-body-sm">
        ▶
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
    <div class="p-6 text-on-surface-variant text-center">Nenhuma música encontrada nas paradas ainda.</div>
<?php endif; ?>
</div>
</div>
</section>

<!-- Section 3: Moods -->
<section>
<h3 class="font-headline-md text-headline-md text-on-surface mb-6">Humores</h3>
<div class="relative group/carousel">
    <!-- Botão de Voltar (Esquerda) -->
    <button 
        onclick="scrollCarrossel('humores-scroll-container', 'left')" 
        class="absolute left-2 top-[50%] -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-surface-container/80 border border-white/10 text-on-surface hover:text-primary flex items-center justify-center backdrop-blur-md opacity-0 group-hover/carousel:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95 shadow-xl pointer-events-auto"
        title="Voltar"
    >
        <span class="material-symbols-outlined text-[24px]">chevron_left</span>
    </button>

    <!-- Botão de Avançar (Direita) -->
    <button 
        onclick="scrollCarrossel('humores-scroll-container', 'right')" 
        class="absolute right-2 top-[50%] -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-surface-container/80 border border-white/10 text-on-surface hover:text-primary flex items-center justify-center backdrop-blur-md opacity-0 group-hover/carousel:opacity-100 transition-all duration-300 hover:scale-110 active:scale-95 shadow-xl pointer-events-auto"
        title="Avançar"
    >
        <span class="material-symbols-outlined text-[24px]">chevron_right</span>
    </button>

    <div id="humores-scroll-container" class="flex overflow-x-auto gap-4 pb-4 hide-scrollbar -mx-4 px-4 md:mx-0 md:px-0">
    <div class="min-w-[160px] md:min-w-[200px] h-32 rounded-2xl relative overflow-hidden group cursor-pointer border border-white/5" data-genre-card="Chill">
    <img alt="Chill Mood" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="https://lh3.googleusercontent.com/aida-public/AB6AXuClnfKZWC6_FSVLYkZ_aPZMik_o33R9np4jE-tYURYBsEvvwETyuf9ByTwqXjv6R7VkSzNWxaOxzLysoC6lJFuW1LihWBThncbC8CwKG_U4n0RSns_pg5Eg2n2j7o-m61SafYgeHQdB9Fr5XvaaDK_QBfKRLktfg3uPHCSr5elnh5VH3QtyaC6MH8sMAo0ZBMSh7yvUPjSmc9lyYCYasdjLcWFmR_WH8S4SavaV7FPhXuxfdTkXlmFVjCn5SQAIacqQji4Z_iBuznE"/>
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
    <div class="absolute inset-0 flex items-center justify-center">
    <span class="font-headline-sm text-headline-sm text-white tracking-wide">Chill</span>
    </div>
    </div>
    <div class="min-w-[160px] md:min-w-[200px] h-32 rounded-2xl relative overflow-hidden group cursor-pointer border border-white/5" data-genre-card="Workout">
    <img alt="Energy Mood" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA0XK7g-3utaD9tsMibn1Aq6XFvUpEJ-KVhVfkI1-xVqUJWvVnMBd0YDGaRvXyc7zFUnELqfKazNl52KPPOooUCGXnd-nuAqRhg48Nuq5JuV5kU9Ixa-Xhs_hK2wqfMwMZRMYmbx1F00xpxHFR0PtlfBGA-ZA-Tkrdnv_asx5MYRtID3afiK-GP0x_KeN5WR9XQYJk0S7PodoflTILFlGn2wj-0_RoM0nHjJ_VMdccq_VlNIq2ok123bKmuuziDVEfkq7ylNyWovbc"/>
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
    <div class="absolute inset-0 flex items-center justify-center">
    <span class="font-headline-sm text-headline-sm text-white tracking-wide">Treino</span>
    </div>
    </div>
    </div>
</div>
</section>

<!-- Genre Modal -->
<div id="genre-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div id="genre-modal-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharGenreModal()"></div>
    <div class="relative bg-surface border border-white/10 rounded-3xl p-8 w-full max-w-2xl max-h-[85vh] overflow-y-auto shadow-2xl z-10">
        <div class="flex justify-between items-center mb-6">
            <h3 id="genre-modal-title" class="font-headline-md text-headline-md text-primary">Gênero</h3>
            <button onclick="fecharGenreModal()" class="text-on-surface-variant hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[28px]">close</span>
            </button>
        </div>
        <div id="genre-modal-list" class="flex flex-col gap-2">
            <!-- Dynamic tracks list -->
        </div>
    </div>
</div>

<script>
function abrirGenreModal(genero) {
    document.getElementById('genre-modal-title').innerText = genero;
    const listContainer = document.getElementById('genre-modal-list');
    listContainer.innerHTML = '<div class="text-on-surface-variant py-4 text-center">Buscando faixas...</div>';
    document.getElementById('genre-modal').classList.remove('hidden');

    fetch(`php/obter_musicas_genero.php?genero=${encodeURIComponent(genero)}`)
        .then(res => res.json())
        .then(tracks => {
            if (tracks.length === 0) {
                listContainer.innerHTML = `<div class="text-on-surface-variant py-8 text-center">Nenhuma faixa encontrada para o gênero "${genero}". Vá para Upload para adicionar faixas!</div>`;
                return;
            }
            
            // Temporary global tracks list to support playing from modal index
            window.modalTracks = tracks;

            listContainer.innerHTML = tracks.map((track, i) => `
                <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer group" onclick="tocarMusicaModal(${i})">
                    <div class="flex items-center gap-4 min-w-0">
                        <img src="${track.thumbnail || 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'}" class="w-12 h-12 rounded object-cover">
                        <div class="truncate">
                            <span class="block font-bold text-on-surface truncate group-hover:text-primary transition-colors">${track.titulo}</span>
                            <span class="block text-sm text-on-surface-variant truncate">${track.artista}</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity">play_circle</span>
                </div>
            `).join('');
        })
        .catch(err => {
            listContainer.innerHTML = '<div class="text-error py-4 text-center">Erro ao buscar faixas.</div>';
        });
}

function fecharGenreModal() {
    document.getElementById('genre-modal').classList.add('hidden');
}

function tocarMusicaModal(indice) {
    if (window.modalTracks && window.modalTracks[indice]) {
        const track = window.modalTracks[indice];
        tocarMusica(
            track.youtube_id || '',
            track.titulo,
            track.artista,
            track.thumbnail,
            null,
            track.id,
            {
                playlist: window.modalTracks,
                audio_url: track.audio_url || ''
            }
        );
    }
}

// Bind genre cards
document.querySelectorAll('[data-genre-card]').forEach(card => {
    card.addEventListener('click', () => {
        const genre = card.getAttribute('data-genre-card');
        abrirGenreModal(genre);
    });
});
</script>

<?php
render_page_end($usuario, $musicas);
?>
