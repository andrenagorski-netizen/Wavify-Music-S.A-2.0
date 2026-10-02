<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

// Fetch user's own musics
$musicas = user_musics($usuario_id);

$capa_padrao = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAE8qW-12I3R5AbdBkyMrbYsfIWcnx5Hfw7KtsNTJPyMHUVYOT7iFXhM2dv-zHwAK_Skvb-MKyyDDkSYTrF7Cs-qwLmp2_Xe-RShetIuoBXsmXdFXlzOC-aqEMkwddXuICfYFY-_gYJcYek11EVxyz8Wp9q04wfaJa6jHxjspngBcOoZWnSlW12xjgXM5VwJ6v0mv7UqZwFbpgoTUbogyiccDWk-UaPs-kncYghGeyMUvFDowiUoxAKdFMOuHEYT0GMaVSe3kjDCZM';
$musica_inicial = count($musicas) > 0 ? $musicas[0] : null;

// Start page rendering
render_page_start('WAVIFY - Minha Biblioteca', 'wavify_my_library.php', $usuario, $musicas);
?>

<header class="mb-10">
<h1 class="page-title mb-6">Minha Biblioteca</h1>
<!-- Tabs -->
<div class="flex gap-8 border-b border-white/10">
<button class="pb-4 font-label-lg text-label-lg text-primary border-b-2 border-primary shadow-[0_10px_20px_-10px_rgba(124,58,237,0.5)] tracking-widest">
                    MÚSICAS
                </button>
<a href="wavify_playlists.php" class="pb-4 font-label-lg text-label-lg text-on-surface-variant hover:text-on-surface transition-colors tracking-widest">
                    PLAYLISTS
                </a>
</div>
</header>

<!-- Adicionar músicas -->
<section class="mb-10">
    <div class="flex items-center gap-3 mb-6">
        <span class="material-symbols-outlined text-primary text-[28px]" style="font-variation-settings: 'FILL' 1;">add_circle</span>
        <h2 class="font-headline-md text-headline-md text-on-surface">Adicionar Música do YouTube</h2>
    </div>
    <div class="bg-surface-container-low/50 backdrop-blur-md rounded-2xl border border-white/5 p-8 relative overflow-hidden">
        <!-- Decorative glow -->
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <p class="font-body-md text-body-md text-on-surface-variant">Cole o link de um vídeo do YouTube para salvar na sua conta.</p>
            <a
                href="https://music.youtube.com"
                target="_blank"
                rel="noopener noreferrer"
                title="Abrir YouTube Music"
                aria-label="Abrir YouTube Music"
                style="
                    display: inline-flex;
                    align-items: center;
                    gap: 9px;
                    padding: 9px 18px 9px 12px;
                    border-radius: 999px;
                    background: linear-gradient(135deg, #FF0033 0%, #CC0000 100%);
                    color: #fff;
                    font-size: 13px;
                    font-weight: 700;
                    letter-spacing: 0.4px;
                    text-decoration: none;
                    flex-shrink: 0;
                    box-shadow: 0 4px 20px rgba(255,0,50,0.4), 0 1px 4px rgba(0,0,0,0.35);
                    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
                    cursor: pointer;
                    white-space: nowrap;
                "
                onmouseover="this.style.transform='scale(1.07)';this.style.boxShadow='0 7px 30px rgba(255,0,50,0.6), 0 2px 8px rgba(0,0,0,0.4)';this.style.filter='brightness(1.12)';"
                onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 20px rgba(255,0,50,0.4), 0 1px 4px rgba(0,0,0,0.35)';this.style.filter='brightness(1)';"
            >
                <!-- SVG real do YouTube Music -->
                <svg width="22" height="22" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));">
                    <!-- Círculo externo branco (anel) -->
                    <circle cx="24" cy="24" r="23" fill="white"/>
                    <!-- Círculo vermelho interno -->
                    <circle cx="24" cy="24" r="14" fill="#FF0000"/>
                    <!-- Círculo branco central (buraco) -->
                    <circle cx="24" cy="24" r="6.5" fill="white"/>
                    <!-- Triângulo play estilizado (nota) -->
                    <polygon points="21,18 21,30 32,24" fill="white" opacity="0"/>
                </svg>
                YouTube Music
            </a>
        </div>
        <form action="adicionar_musica.php" method="POST" class="flex flex-col gap-4 relative z-10" id="formAdicionarMusica">
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors text-[20px]">
                    link
                </span>
                <input
                    type="text"
                    name="youtube_link"
                    id="youtube_link"
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 pl-12 pr-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                    required
                />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input
                    type="text"
                    name="titulo"
                    id="youtube_title"
                    placeholder="Nome da música (opcional se colar link)"
                    class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 px-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                />
                <input
                    type="text"
                    name="artista"
                    id="youtube_artist"
                    placeholder="Nome do artista (opcional se colar link)"
                    class="w-full bg-surface-container/80 border border-white/10 focus:border-primary/60 focus:ring-2 focus:ring-primary/30 rounded-full py-3 px-5 text-on-surface placeholder:text-on-surface-variant/40 font-body-md text-body-md outline-none transition-all duration-300 shadow-inner"
                />
            </div>

            <button
                type="submit"
                class="flex items-center justify-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-primary-container to-inverse-primary text-white font-label-lg text-label-lg shadow-[0_0_20px_rgba(124,58,237,0.4)] hover:shadow-[0_0_30px_rgba(124,58,237,0.7)] hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
            >
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">
                    add
                </span>
                Adicionar Música
            </button>
        </form>
    </div>
</section>  

<!-- Actions -->
<div class="flex gap-4 mb-8">
<button onclick="ouvirTudo()" class="flex items-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-primary-container to-inverse-primary text-white font-label-lg text-label-lg shadow-[0_0_20px_rgba(124,58,237,0.4)] hover:shadow-[0_0_30px_rgba(124,58,237,0.6)] hover:scale-[1.02] transition-all">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                Ouvir Tudo
            </button>
<button onclick="tocarAleatorio()" class="flex items-center gap-2 px-8 py-3 rounded-full bg-transparent border border-primary/50 text-primary font-label-lg text-label-lg hover:bg-primary/10 hover:shadow-[0_0_15px_rgba(124,58,237,0.2)] transition-all">
<span class="material-symbols-outlined">shuffle</span>
                Aleatório
            </button>
</div>

<!-- Track List -->
<div class="bg-surface-container-low/50 backdrop-blur-md rounded-xl border border-white/5 overflow-hidden">
<!-- Table Header -->
<div class="grid grid-cols-[auto_1fr_1fr_1fr_auto_auto] gap-4 px-6 py-4 border-b border-white/5 text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
<div class="w-8 text-center">#</div>
<div>Título</div>
<div class="hidden md:block">Artista</div>
<div class="hidden lg:block">Origem / Álbum</div>
<div class="hidden xl:block">Adicionado em</div>
<div class="w-24 text-right">
<span class="material-symbols-outlined text-[16px]">schedule</span>
</div>
</div>

<!-- List -->
<div class="flex flex-col">
<?php if (count($musicas) > 0): ?>
<?php foreach($musicas as $indice => $musica): ?>
<div
id="musica-item-<?= $musica['id'] ?>"
class="group grid grid-cols-[auto_1fr_1fr_1fr_auto_auto] gap-4 px-6 py-3 items-center hover:bg-white/5 transition-colors cursor-pointer border-b border-white/5 last:border-0 relative overflow-hidden"
data-search-item
onclick="tocarMusicaPorId(<?= $musica['id'] ?>)"
>
    <div class="w-8 text-center">
        <?= $indice + 1 ?>
    </div>

    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded overflow-hidden">
            <img
                src="<?= htmlspecialchars($musica['thumbnail'] ?: $capa_padrao) ?>"
                class="w-full h-full object-cover">
        </div>
        <div class="flex flex-col">
            <span class="font-body-md text-on-surface">
                <?= htmlspecialchars($musica['titulo']) ?>
            </span>
            <span class="font-body-sm text-on-surface-variant md:hidden">
                <?= htmlspecialchars($musica['artista']) ?>
            </span>
        </div>
    </div>

    <div class="hidden md:block">
        <?= htmlspecialchars($musica['artista']) ?>
    </div>

    <div class="hidden lg:block">
        <?= htmlspecialchars($musica['origem'] === 'upload' ? ($musica['album'] ?: 'Upload') : 'YouTube') ?>
    </div>

    <div class="hidden xl:block">
        <?= $musica['data_adicao'] ?>
    </div>

    <div class="w-24 text-right flex items-center justify-end gap-2">
        <!-- Botão de Apagar Música -->
        <button class="text-on-surface-variant hover:text-error transition-colors p-1" title="Apagar música" onclick="event.stopPropagation(); window.confirmarExclusaoMusica(<?= $musica['id'] ?>, <?= htmlspecialchars(json_encode($musica['titulo']), ENT_QUOTES, 'UTF-8') ?>)">
            <span class="material-symbols-outlined text-[20px]">delete</span>
        </button>
        <!-- Playlist Menu Trigger -->
        <button class="playlist-add-btn text-on-surface-variant hover:text-primary transition-colors p-1" data-track-id="<?= $musica['id'] ?>" onclick="event.stopPropagation(); window.openPlaylistMenu(event, <?= $musica['id'] ?>)">
            <span class="material-symbols-outlined">playlist_add</span>
        </button>
        <span class="text-xs">▶</span>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="px-6 py-8 text-on-surface-variant text-center">
    Nenhuma música encontrada na sua biblioteca.
</div>
<?php endif; ?>
</div>
</div>

<script>
window.mostrarToastFeedback = function(mensagem, tipo = 'success') {
    const toastExistente = document.getElementById('wavify-toast');
    if (toastExistente) {
        toastExistente.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'wavify-toast';
    
    const cores = tipo === 'success' 
        ? 'border-primary/30 bg-surface-container/90 text-primary shadow-[0_0_30px_rgba(124,58,237,0.3)]' 
        : 'border-error/30 bg-surface-container/90 text-error shadow-[0_0_30px_rgba(239,68,68,0.3)]';
        
    const icone = tipo === 'success' ? 'check_circle' : 'error';

    toast.className = `fixed top-6 right-6 z-[9999] flex items-center gap-3 px-6 py-4 rounded-2xl border backdrop-blur-xl ${cores} transition-all duration-500 transform translate-y-[-20px] opacity-0 font-body-md`;
    
    toast.innerHTML = `
        <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">${icone}</span>
        <span>${mensagem}</span>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-[-20px]', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-[-20px]', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 500);
    }, 3500);
};

window.confirmarExclusaoMusica = function(id, titulo) {
    const modalExistente = document.getElementById('wavify-confirm-modal');
    if (modalExistente) modalExistente.remove();

    const modal = document.createElement('div');
    modal.id = 'wavify-confirm-modal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300';
    
    modal.innerHTML = `
        <div class="modal-card bg-surface-container border border-white/10 p-6 md:p-8 rounded-3xl max-w-md w-full shadow-2xl transform scale-95 transition-all duration-300 relative overflow-hidden text-on-surface">
            <div class="absolute -top-20 -right-20 w-48 h-48 bg-error/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-error/10 flex items-center justify-center text-error shrink-0">
                    <span class="material-symbols-outlined text-[28px]">delete_forever</span>
                </div>
                <h3 class="font-headline-md text-headline-sm text-on-surface">Apagar música?</h3>
            </div>
            
            <p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed">
                Tem certeza que deseja apagar a música <strong class="text-on-surface">"${titulo}"</strong> da sua biblioteca? Esta ação não pode ser desfeita e ela também será removida de suas playlists.
            </p>
            
            <div class="flex justify-end gap-3">
                <button id="btn-cancel-del" class="px-5 py-2.5 rounded-full font-label-lg text-label-lg text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all">
                    Cancelar
                </button>
                <button id="btn-confirm-del" class="px-6 py-2.5 rounded-full font-label-lg text-label-lg bg-gradient-to-r from-error to-red-500 hover:from-red-500 hover:to-red-600 text-white shadow-lg shadow-error/20 hover:shadow-error/30 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Apagar Música
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const card = modal.querySelector('.modal-card');

    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (card) {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }
    }, 10);

    const fechar = () => {
        modal.classList.add('opacity-0');
        if (card) {
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }
        setTimeout(() => modal.remove(), 300);
    };

    modal.querySelector('#btn-cancel-del').addEventListener('click', fechar);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) fechar();
    });

    modal.querySelector('#btn-confirm-del').addEventListener('click', () => {
        fechar();
        window.executarExclusaoMusica(id, titulo);
    });
};

window.executarExclusaoMusica = function(id, titulo) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('php/deletar_musica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Efeito visual de fade-out na linha
            const item = document.getElementById('musica-item-' + id);
            if (item) {
                item.style.transition = 'all 0.5s ease';
                item.style.opacity = '0';
                item.style.height = '0';
                item.style.paddingTop = '0';
                item.style.paddingBottom = '0';
                setTimeout(() => {
                    item.remove();
                    
                    // Ajustar os índices das músicas restantes
                    const items = document.querySelectorAll('[id^="musica-item-"]');
                    if (items.length === 0) {
                        const listContainer = document.querySelector('.flex.flex-col');
                        if (listContainer) {
                            listContainer.innerHTML = '<div class="px-6 py-8 text-on-surface-variant text-center">Nenhuma música encontrada na sua biblioteca.</div>';
                        }
                    } else {
                        items.forEach((el, idx) => {
                            el.setAttribute('onclick', `tocarMusicaPorIndice(${idx})`);
                            const indexDiv = el.querySelector('.w-8.text-center');
                            if (indexDiv) {
                                indexDiv.innerText = idx + 1;
                            }
                        });
                    }
                }, 500);
            }

            // Atualizar o player local
            if (window.wavifyMusicas) {
                if (typeof trackAtual !== 'undefined' && trackAtual && trackAtual.id === id) {
                    if (typeof proximaMusica === 'function') {
                        proximaMusica();
                    }
                }

                window.wavifyMusicas = window.wavifyMusicas.filter(m => m.id !== id);

                if (typeof playlistInicial !== 'undefined') {
                    playlistInicial.length = 0;
                    window.wavifyMusicas.forEach(t => playlistInicial.push(t));
                }
            }

            window.mostrarToastFeedback(`"${titulo}" foi removida com sucesso!`, 'success');
        } else {
            window.mostrarToastFeedback(data.erro || 'Erro ao remover música.', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        window.mostrarToastFeedback('Erro de conexão ao remover música.', 'error');
    });
};

// Manutencao 06 e 07 - Validacao do Link de musica
document.addEventListener('DOMContentLoaded', () => {
    const formAdicionar = document.getElementById('formAdicionarMusica');
    if (formAdicionar) {
        formAdicionar.addEventListener('submit', (e) => {
            const linkInput = document.getElementById('youtube_link').value;
            // Verifica se o link é YouTube ou Spotify
            const ehYoutube = linkInput.includes('youtube.com') || linkInput.includes('youtu.be');
            const ehSpotify = linkInput.includes('spotify.com');
            
            if (!ehYoutube && !ehSpotify) {
                e.preventDefault();
                window.mostrarToastFeedback('Opção inválida', 'error');
            }
        });
    }
});
</script>

<?php
render_page_end($usuario, $musicas);
?>
