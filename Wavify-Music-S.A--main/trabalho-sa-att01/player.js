let player;
let playerAudioLocal = new Audio();
let musicaAtual = -1;
let aleatorioAtivo = false;
let repetirAtivo = false;
let progressoIntervalo = null;
let volumeAntesDeMutar = 100;
let arrastandoProgresso = false;
let arrastandoVolume = false;
let videoAtualId = null;
let trackAtual = null;
let playerPronto = false;
let aplicandoComandoRemoto = false;
let comandoPendente = null;
let playlistCompartilhada = [];

const musicas = window.wavifyMusicas || [];
const playlistInicial = musicas;
const PLAYER_STATE_KEY = 'wavify_player_state';
const PLAYER_COMMAND_KEY = 'wavify_player_command';
const PLAYER_TAB_ID = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

document.addEventListener('DOMContentLoaded', () => {
    configurarArrasteBarras();
    configurarControlesPlayerGenericos();
    escutarSincroniaPlayer();
    configurarAudioLocal();
});

function configurarAudioLocal() {
    playerAudioLocal.addEventListener('ended', () => {
        if (repetirAtivo) {
            playerAudioLocal.currentTime = 0;
            playerAudioLocal.play();
        } else {
            proximaMusica();
        }
    });

    playerAudioLocal.addEventListener('play', () => {
        atualizarIconePlayPause();
        iniciarAtualizacaoProgresso();
    });

    playerAudioLocal.addEventListener('pause', () => {
        atualizarIconePlayPause();
    });
}

function onYouTubeIframeAPIReady() {
    garantirContainerYoutube();

    player = new YT.Player('youtube-player', {
        width: '1',
        height: '1',
        videoId: '',
        playerVars: {
            autoplay: 0,
            controls: 0,
            rel: 0
        },
        events: {
            onReady: () => {
                playerPronto = true;
                player.setVolume(volumeAntesDeMutar);
                atualizarVolumeVisual(volumeAntesDeMutar);
                atualizarIconePlayPause();
                iniciarAtualizacaoProgresso();
                restaurarEstadoCompartilhado();
            },
            onStateChange: aoMudarEstadoPlayer
        }
    });
}

function garantirContainerYoutube() {
    if (document.getElementById('youtube-player')) return;

    const container = document.createElement('div');
    container.id = 'youtube-player';
    container.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;';
    document.body.appendChild(container);
}

function obterPlaylistAtiva() {
    if (playlistCompartilhada.length) {
        return playlistCompartilhada;
    }

    if (Array.isArray(window.wavifyMusicas) && window.wavifyMusicas.length) {
        return window.wavifyMusicas;
    }

    return playlistInicial;
}

function serializarPlaylistAtual() {
    const playlist = playlistCompartilhada.some((musica) => 
        (trackAtual?.youtube_id && musica.youtube_id === trackAtual?.youtube_id) || 
        (trackAtual?.audio_url && musica.audio_url === trackAtual?.audio_url)
    ) ? playlistCompartilhada : obterPlaylistAtiva();

    return playlist.map((musica) => ({
        id: musica.id || null,
        youtube_id: musica.youtube_id || '',
        audio_url: musica.audio_url || '',
        titulo: musica.titulo,
        artista: musica.artista,
        thumbnail: musica.thumbnail
    })).filter((musica) => musica.youtube_id || musica.audio_url);
}

function normalizarPlaylist(playlist) {
    if (!Array.isArray(playlist)) return [];

    return playlist.map((musica) => ({
        id: musica.id || null,
        youtube_id: musica.youtube_id || '',
        audio_url: musica.audio_url || '',
        titulo: musica.titulo || 'Nenhuma musica',
        artista: musica.artista || 'Artista desconhecido',
        thumbnail: musica.thumbnail || ''
    })).filter((musica) => musica.youtube_id || musica.audio_url);
}

function obterMusicaAtual() {
    if (trackAtual) return trackAtual;

    const playlist = obterPlaylistAtiva();
    const musica = playlist[musicaAtual];

    if (musica) return musica;

    const estadoSalvo = lerJsonLocalStorage(PLAYER_STATE_KEY);
    return estadoSalvo?.track || null;
}

function estadoAtualPlayer(isPlaying = null) {
    const track = obterMusicaAtual();
    const isLocal = track && !track.youtube_id && track.audio_url;
    let playingState = false;
    let time = 0;

    if (isLocal) {
        playingState = !playerAudioLocal.paused;
        time = playerAudioLocal.currentTime || 0;
    } else {
        const estado = player && player.getPlayerState ? player.getPlayerState() : null;
        playingState = estado === YT.PlayerState.PLAYING;
        time = player && player.getCurrentTime ? player.getCurrentTime() || 0 : 0;
    }

    return {
        track,
        indice: musicaAtual,
        playlist: serializarPlaylistAtual(),
        currentTime: time,
        isPlaying: isPlaying === null ? playingState : isPlaying
    };
}

function publicarComandoPlayer(acao, dados = {}) {
    const payload = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        origem: PLAYER_TAB_ID,
        acao,
        ...dados,
        updatedAt: Date.now()
    };

    localStorage.setItem(PLAYER_STATE_KEY, JSON.stringify({
        track: payload.track,
        indice: payload.indice,
        playlist: payload.playlist,
        currentTime: payload.currentTime || 0,
        isPlaying: Boolean(payload.isPlaying),
        updatedAt: payload.updatedAt
    }));

    localStorage.setItem(PLAYER_COMMAND_KEY, JSON.stringify(payload));
}

function escutarSincroniaPlayer() {
    window.addEventListener('storage', (event) => {
        if (event.key !== PLAYER_COMMAND_KEY || !event.newValue) return;

        const comando = lerJson(event.newValue);
        if (!comando || comando.origem === PLAYER_TAB_ID) return;

        aplicarComandoPlayer(comando);
    });
}

function restaurarEstadoCompartilhado() {
    if (comandoPendente) {
        aplicarComandoPlayer(comandoPendente);
        comandoPendente = null;
        return;
    }

    const estado = lerJsonLocalStorage(PLAYER_STATE_KEY);
    if (!estado?.track?.youtube_id && !estado?.track?.audio_url) return;

    aplicarComandoPlayer({
        acao: estado.isPlaying ? 'play-track' : 'pause',
        ...estado,
        origem: 'local-restore'
    });
}
function tocarMusicaPorId(id) {
    // Sempre priorizar a playlist da página atual para encontrar a música clicada
    const playlist = (Array.isArray(window.wavifyMusicas) && window.wavifyMusicas.length)
        ? window.wavifyMusicas
        : obterPlaylistAtiva();

    const indice = playlist.findIndex(m => Number(m.id) === Number(id));

    if (indice === -1) {
        console.warn("Música não encontrada no player:", id);
        return;
    }

    tocarMusicaPorIndice(indice, { playlist });
}

function aplicarComandoPlayer(comando) {
    if (comando.playlist) {
        playlistCompartilhada = normalizarPlaylist(comando.playlist);
    }

    const track = comando.track || playlistCompartilhada[comando.indice];
    const tempo = calcularTempoSincronizado(comando);
    const isLocal = track && !track.youtube_id && track.audio_url;

    if (!isLocal && (!player || !playerPronto)) {
        comandoPendente = comando;
        return;
    }

    aplicandoComandoRemoto = true;

    if ((comando.acao === 'play-track' || comando.acao === 'resume') && (track?.youtube_id || track?.audio_url)) {
        tocarMusica(
            track.youtube_id || '',
            track.titulo,
            track.artista,
            track.thumbnail,
            comando.indice,
            track.id,
            {
                sincronizar: false,
                registrar: false,
                inicio: tempo,
                playlist: playlistCompartilhada,
                audio_url: track.audio_url || ''
            }
        );
    }

    if (comando.acao === 'pause' && (track?.youtube_id || track?.audio_url)) {
        carregarMusicaPausada(track, tempo, comando.indice);
    }

    if (comando.acao === 'seek') {
        if ((track?.youtube_id && videoAtualId !== track.youtube_id) || (isLocal && playerAudioLocal.src !== track.audio_url)) {
            tocarMusica(
                track.youtube_id || '',
                track.titulo,
                track.artista,
                track.thumbnail,
                comando.indice,
                track.id,
                {
                    sincronizar: false,
                    registrar: false,
                    inicio: tempo,
                    playlist: playlistCompartilhada,
                    audio_url: track.audio_url || ''
                }
            );
        } else {
            if (isLocal) {
                playerAudioLocal.currentTime = tempo;
            } else if (player.seekTo) {
                player.seekTo(tempo, true);
            }
        }

        if (!comando.isPlaying) {
            setTimeout(() => {
                if (isLocal) {
                    playerAudioLocal.pause();
                } else {
                    player.pauseVideo();
                }
                atualizarIconePlayPause();
            }, 150);
        }
    }

    setTimeout(() => {
        aplicandoComandoRemoto = false;
        atualizarIconePlayPause();
    }, 200);
}

function carregarMusicaPausada(track, tempo = 0, indice = null) {
    const isLocal = track && !track.youtube_id && track.audio_url;
    videoAtualId = track.youtube_id || null;
    trackAtual = track;

    if (indice !== null && indice !== undefined) {
        musicaAtual = Number(indice);
    }

    if (isLocal) {
        playerAudioLocal.src = track.audio_url;
        playerAudioLocal.currentTime = tempo;
        playerAudioLocal.pause();
    } else {
        if (player.cueVideoById) {
            player.cueVideoById({
                videoId: track.youtube_id,
                startSeconds: tempo || 0
            });
        }
    }

    atualizarInterfaceMusica(track);
    const duracao = isLocal ? (playerAudioLocal.duration || 0) : (player.getDuration ? player.getDuration() || 0 : 0);
    atualizarProgressoVisual(tempo || 0, duracao);

    setTimeout(() => {
        if (isLocal) {
            playerAudioLocal.pause();
        } else {
            if (player.pauseVideo) player.pauseVideo();
        }
        atualizarIconePlayPause();
    }, 150);
}

function calcularTempoSincronizado(comando) {
    const tempoBase = Number(comando.currentTime || 0);

    if (!comando.isPlaying || !comando.updatedAt) {
        return tempoBase;
    }

    const segundosPassados = (Date.now() - comando.updatedAt) / 1000;
    return Math.max(0, tempoBase + segundosPassados);
}

function sincronizarBusca(tempo) {
    if (aplicandoComandoRemoto) return;

    const track = obterMusicaAtual();
    const isLocal = track && !track.youtube_id && track.audio_url;
    const isPlaying = isLocal ? !playerAudioLocal.paused : (player.getPlayerState && player.getPlayerState() === YT.PlayerState.PLAYING);

    publicarComandoPlayer('seek', {
        ...estadoAtualPlayer(isPlaying),
        currentTime: tempo
    });
}

function lerJsonLocalStorage(chave) {
    return lerJson(localStorage.getItem(chave));
}

function lerJson(valor) {
    try {
        return valor ? JSON.parse(valor) : null;
    } catch (erro) {
        return null;
    }
}

function tocarMusica(videoId, titulo, artista, thumbnail, indice = null, musicaId = null, opcoes = {}) {
    const opcoesFinais = {
        sincronizar: true,
        registrar: true,
        inicio: 0,
        playlist: null,
        audio_url: '',
        ...opcoes
    };

    if (indice !== null) {
        musicaAtual = Number(indice);
    } else {
        const playlist = opcoesFinais.playlist || obterPlaylistAtiva();
        musicaAtual = playlist.findIndex((m) => (videoId && m.youtube_id === videoId) || (opcoesFinais.audio_url && m.audio_url === opcoesFinais.audio_url));
    }

    const playlistAtiva = opcoesFinais.playlist || obterPlaylistAtiva();
    const audioUrl = opcoesFinais.audio_url || (playlistAtiva[musicaAtual]?.audio_url) || '';
    const isLocal = !videoId && audioUrl;

    videoAtualId = videoId || null;
    trackAtual = { id: musicaId, youtube_id: videoId, audio_url: audioUrl, titulo, artista, thumbnail };

    if (isLocal) {
        // Pause YouTube
        if (playerPronto && player && player.pauseVideo) {
            try { player.pauseVideo(); } catch (e) {}
        }
        
        // Play local
        playerAudioLocal.src = audioUrl;
        playerAudioLocal.currentTime = opcoesFinais.inicio || 0;
        playerAudioLocal.volume = (volumeAntesDeMutar || 100) / 100;
        playerAudioLocal.play().catch((err) => console.log("Play local falhou: ", err));
    } else {
        // Pause local
        playerAudioLocal.pause();

        if (!player || !playerPronto) {
            comandoPendente = {
                acao: 'play-track',
                track: { id: musicaId, youtube_id: videoId, audio_url: audioUrl, titulo, artista, thumbnail },
                indice,
                playlist: opcoesFinais.playlist,
                currentTime: opcoesFinais.inicio || 0
            };
            return;
        }

        player.loadVideoById({
            videoId,
            startSeconds: opcoesFinais.inicio || 0
        });
        player.playVideo();
    }

    atualizarInterfaceMusica({ titulo, artista, thumbnail });
    atualizarIconePlayPause();
    
    if (isLocal) {
        playerAudioLocal.addEventListener('loadedmetadata', () => {
            atualizarProgressoVisual(opcoesFinais.inicio || 0, playerAudioLocal.duration || 0);
        }, { once: true });
    } else {
        atualizarProgressoVisual(opcoesFinais.inicio || 0, player.getDuration ? player.getDuration() || 0 : 0);
    }

    if (opcoesFinais.registrar) {
        registrarReproducao(musicaId);
    }

    if (opcoesFinais.sincronizar && !aplicandoComandoRemoto) {
        publicarComandoPlayer('play-track', {
            track: { id: musicaId, youtube_id: videoId, audio_url: audioUrl, titulo, artista, thumbnail },
            indice: musicaAtual,
            playlist: serializarPlaylistAtual(),
            currentTime: opcoesFinais.inicio || 0,
            isPlaying: true
        });
    }
}

function atualizarInterfaceMusica({ titulo, artista, thumbnail }) {
    const tituloPlayer = document.getElementById('player-titulo');
    const artistaPlayer = document.getElementById('player-artista');
    const capaPlayer = document.getElementById('player-capa');

    if (tituloPlayer) tituloPlayer.innerText = titulo;
    if (artistaPlayer) artistaPlayer.innerText = artista;
    if (capaPlayer) capaPlayer.src = thumbnail;

    atualizarPlayerGenerico(titulo, artista, thumbnail);
    atualizarHeroMusica(titulo, artista, thumbnail);
}

function atualizarPlayerGenerico(titulo, artista, thumbnail) {
    const barras = document.querySelectorAll('footer.fixed.bottom-0, nav.fixed.bottom-0');

    barras.forEach((barra) => {
        if (!barra.querySelector('#player-titulo')) {
            const tituloGenerico = barra.querySelector('h4') || barra.querySelector('a.font-label-lg');
            if (tituloGenerico) tituloGenerico.innerText = titulo;
        }

        if (!barra.querySelector('#player-artista')) {
            const artistaGenerico = barra.querySelector('p') || barra.querySelector('.font-body-sm');
            if (artistaGenerico) artistaGenerico.innerText = artista;
        }

        if (!barra.querySelector('#player-capa')) {
            const capaGenerica = barra.querySelector('img');
            if (capaGenerica && thumbnail) capaGenerica.src = thumbnail;
        }
    });
}

function configurarControlesPlayerGenericos() {
    const icones = document.querySelectorAll('footer.fixed.bottom-0 button .material-symbols-outlined, nav.fixed.bottom-0 button .material-symbols-outlined');

    icones.forEach((icone) => {
        const botao = icone.closest('button');
        if (!botao || botao.dataset.wavifyPlayerControl === 'true' || botao.getAttribute('onclick')) return;

        const acao = icone.innerText.trim();
        const acoes = {
            play_arrow: playPauseMusica,
            pause: playPauseMusica,
            skip_next: proximaMusica,
            skip_previous: musicaAnterior,
            volume_up: alternarMudo,
            volume_down: alternarMudo,
            volume_off: alternarMudo,
            shuffle: alternarAleatorio,
            repeat: alternarRepetir
        };

        if (!acoes[acao]) return;

        botao.dataset.wavifyPlayerControl = 'true';
        botao.addEventListener('click', (event) => {
            event.preventDefault();
            acoes[acao]();
        });
    });
}

function atualizarHeroMusica(titulo, artista, thumbnail) {
    const tituloHero = document.getElementById('hero-titulo');
    const artistaHero = document.getElementById('hero-artista');
    const capaHero = document.getElementById('hero-capa-fundo');

    if (tituloHero) tituloHero.innerText = titulo;
    if (artistaHero) artistaHero.innerText = artista;
    if (capaHero) capaHero.src = thumbnail;
}

function tocarMusicaPorIndice(indice, opcoes = {}) {
    const playlist = opcoes.playlist || (
        Array.isArray(window.wavifyMusicas)
        ? window.wavifyMusicas
        : obterPlaylistAtiva()
    );
    if (!playlist.length) return;

    const total = playlist.length;
    const indiceNormalizado = ((indice % total) + total) % total;
    const musica = playlist[indiceNormalizado];

    tocarMusica(
        musica.youtube_id || '',
        musica.titulo,
        musica.artista,
        musica.thumbnail,
        indiceNormalizado,
        musica.id,
        {
            ...opcoes,
            playlist,
            audio_url: musica.audio_url || ''
        }
    );
}

function registrarReproducao(musicaId) {
    if (!musicaId) return;

    const dados = new FormData();
    dados.append('musica_id', musicaId);

    fetch('php/registrar_reproducao.php', {
        method: 'POST',
        body: dados,
        credentials: 'same-origin'
    }).catch(() => {});
}

function ouvirTudo() {
    tocarMusicaPorIndice(0);
}

function tocarAleatorio() {
    const playlist = obterPlaylistAtiva();
    if (!playlist.length) return;
    const indice = Math.floor(Math.random() * playlist.length);
    tocarMusicaPorIndice(indice);
}

function proximaMusica(opcoes = {}) {
    if (!obterPlaylistAtiva().length) return;

    if (aleatorioAtivo) {
        tocarAleatorio();
        return;
    }

    tocarMusicaPorIndice(musicaAtual + 1, opcoes);
}

function musicaAnterior(opcoes = {}) {
    const track = obterMusicaAtual();
    const isLocal = track && !track.youtube_id && track.audio_url;

    if (isLocal) {
        if (playerAudioLocal.currentTime > 3) {
            playerAudioLocal.currentTime = 0;
            if (opcoes.sincronizar !== false) {
                sincronizarBusca(0);
            }
            return;
        }
    } else {
        if (player && player.getCurrentTime && player.getCurrentTime() > 3) {
            player.seekTo(0, true);
            if (opcoes.sincronizar !== false) {
                sincronizarBusca(0);
            }
            return;
        }
    }

    tocarMusicaPorIndice(musicaAtual - 1, opcoes);
}

function playPauseMusica(opcoes = {}) {
    if (musicaAtual === -1 && obterPlaylistAtiva().length) {
        ouvirTudo();
        return;
    }

    const track = obterMusicaAtual();
    const isLocal = track && !track.youtube_id && track.audio_url;

    if (isLocal) {
        if (playerAudioLocal.paused) {
            playerAudioLocal.play();
            if (opcoes.sincronizar !== false && !aplicandoComandoRemoto) {
                publicarComandoPlayer('resume', estadoAtualPlayer(true));
            }
        } else {
            playerAudioLocal.pause();
            if (opcoes.sincronizar !== false && !aplicandoComandoRemoto) {
                publicarComandoPlayer('pause', estadoAtualPlayer(false));
            }
        }
    } else {
        if (!player) return;
        const estado = player.getPlayerState();

        if (estado === YT.PlayerState.PLAYING) {
            player.pauseVideo();
            if (opcoes.sincronizar !== false && !aplicandoComandoRemoto) {
                publicarComandoPlayer('pause', estadoAtualPlayer(false));
            }
        } else {
            player.playVideo();
            if (opcoes.sincronizar !== false && !aplicandoComandoRemoto) {
                publicarComandoPlayer('resume', estadoAtualPlayer(true));
            }
        }
    }

    setTimeout(atualizarIconePlayPause, 100);
}

function alternarAleatorio() {
    aleatorioAtivo = !aleatorioAtivo;
    alternarClasseAtiva('shuffle-button', aleatorioAtivo);
}

function alternarRepetir() {
    repetirAtivo = !repetirAtivo;
    alternarClasseAtiva('repeat-button', repetirAtivo);
}

function alternarClasseAtiva(id, ativo) {
    const botao = document.getElementById(id);
    if (!botao) return;
    botao.classList.toggle('text-primary', ativo);
    botao.classList.toggle('text-on-surface-variant', !ativo);
}

function aoMudarEstadoPlayer(event) {
    atualizarIconePlayPause();

    if (event.data === YT.PlayerState.PLAYING) {
        iniciarAtualizacaoProgresso();
    }

    if (event.data === YT.PlayerState.ENDED) {
        if (repetirAtivo) {
            player.seekTo(0, true);
            player.playVideo();
        } else {
            proximaMusica();
        }
    }
}

function atualizarIconePlayPause() {
    const track = obterMusicaAtual();
    const isLocal = track && !track.youtube_id && track.audio_url;
    const tocando = isLocal ? !playerAudioLocal.paused : (player && player.getPlayerState ? player.getPlayerState() === YT.PlayerState.PLAYING : false);

    const icones = [
        document.getElementById('icone-play-pause'),
        document.getElementById('hero-icone-play-pause'),
        ...document.querySelectorAll('footer.fixed.bottom-0 button .material-symbols-outlined, nav.fixed.bottom-0 button .material-symbols-outlined')
    ].filter((icone) => {
        if (!icone) return false;
        if (icone.id === 'icone-volume') return false; // Excluir ícone de volume
        const texto = icone.innerText.trim();
        return icone.id || texto === 'play_arrow' || texto === 'pause';
    });

    icones.forEach((icone) => {
        icone.innerText = tocando ? 'pause' : 'play_arrow';

        if (icone.id === 'hero-icone-play-pause') {
            icone.classList.toggle('ml-2', !tocando);
            icone.classList.toggle('ml-0', tocando);
        }

        if (icone.parentElement) {
            icone.parentElement.classList.toggle('animate-pulse-slow', tocando);
            icone.parentElement.setAttribute('aria-label', tocando ? 'Pausar musica' : 'Tocar musica');
        }
    });

    const statusHero = document.getElementById('hero-status');
    if (statusHero) {
        if (!obterPlaylistAtiva().length) {
            statusHero.innerText = 'Sem musica';
        } else {
            statusHero.innerText = tocando ? 'Tocando agora' : (musicaAtual === -1 ? 'Pronta para tocar' : 'Pausado');
        }
    }

    const waveformHero = document.getElementById('hero-waveform');
    if (waveformHero) {
        waveformHero.classList.toggle('opacity-80', tocando);
        waveformHero.classList.toggle('opacity-40', !tocando);
    }
}

function iniciarAtualizacaoProgresso() {
    if (progressoIntervalo) return;

    progressoIntervalo = setInterval(() => {
        const track = obterMusicaAtual();
        if (!track) return;

        const isLocal = !track.youtube_id && track.audio_url;
        let duracao = 0;
        let atual = 0;

        if (isLocal) {
            duracao = playerAudioLocal.duration || 0;
            atual = playerAudioLocal.currentTime || 0;
        } else {
            if (!player || !player.getDuration) return;
            duracao = player.getDuration() || 0;
            atual = player.getCurrentTime() || 0;
        }

        atualizarProgressoVisual(atual, duracao);
    }, 500);
}

function atualizarProgressoVisual(atual, duracao) {
    const tempoAtual = document.getElementById('tempo-atual');
    const tempoDuracao = document.getElementById('tempo-duracao');
    const progresso = document.getElementById('progresso-musica');
    const controle = document.getElementById('controle-progresso');

    if (tempoAtual) tempoAtual.innerText = formatarTempo(atual);
    if (tempoDuracao) tempoDuracao.innerText = formatarTempo(duracao);

    if (progresso) {
        const porcentagem = duracao > 0 ? (atual / duracao) * 100 : 0;
        const porcentagemLimitada = Math.min(Math.max(porcentagem, 0), 100);
        progresso.style.width = `${porcentagemLimitada}%`;
        if (controle) controle.style.left = `${porcentagemLimitada}%`;
    }
}

function alterarProgresso(event) {
    const track = obterMusicaAtual();
    if (!track) return;

    const isLocal = !track.youtube_id && track.audio_url;
    const barra = document.getElementById('barra-progresso');
    let duracao = 0;

    if (isLocal) {
        duracao = playerAudioLocal.duration || 0;
    } else {
        if (!player || !player.getDuration) return;
        duracao = player.getDuration();
    }

    if (!barra || !duracao) return;

    const porcentagem = calcularPorcentagemPonteiro(event, barra);
    const novoTempo = duracao * porcentagem;

    if (isLocal) {
        playerAudioLocal.currentTime = novoTempo;
    } else {
        player.seekTo(novoTempo, true);
    }
    sincronizarBusca(novoTempo);
}

function alterarVolume(event) {
    const barra = document.getElementById('barra-volume');
    if (!barra) return;

    const porcentagem = calcularPorcentagemPonteiro(event, barra);
    const volume = Math.round(porcentagem * 100);

    if (player && player.setVolume) {
        player.setVolume(volume);
    }
    playerAudioLocal.volume = volume / 100;

    if (volume > 0) {
        if (player && player.unMute) player.unMute();
        playerAudioLocal.muted = false;
        volumeAntesDeMutar = volume;
    }
    atualizarVolumeVisual(volume);
}

function configurarArrasteBarras() {
    const barraProgresso = document.getElementById('barra-progresso');
    const barraVolume = document.getElementById('barra-volume');

    if (barraProgresso) {
        barraProgresso.style.touchAction = 'none';
        barraProgresso.addEventListener('pointerdown', iniciarArrasteProgresso);
    }

    if (barraVolume) {
        barraVolume.style.touchAction = 'none';
        barraVolume.addEventListener('pointerdown', iniciarArrasteVolume);
    }

    document.addEventListener('pointermove', moverArraste);
    document.addEventListener('pointerup', finalizarArraste);
    document.addEventListener('pointercancel', finalizarArraste);
}

function iniciarArrasteProgresso(event) {
    arrastandoProgresso = true;
    document.body.classList.add('select-none');
    atualizarProgressoPeloPonteiro(event);
}

function iniciarArrasteVolume(event) {
    arrastandoVolume = true;
    document.body.classList.add('select-none');
    atualizarVolumePeloPonteiro(event);
}

function moverArraste(event) {
    if (arrastandoProgresso) {
        atualizarProgressoPeloPonteiro(event);
    }

    if (arrastandoVolume) {
        atualizarVolumePeloPonteiro(event);
    }
}

function finalizarArraste() {
    arrastandoProgresso = false;
    arrastandoVolume = false;
    document.body.classList.remove('select-none');
}

function atualizarProgressoPeloPonteiro(event) {
    const track = obterMusicaAtual();
    if (!track) return;

    const isLocal = !track.youtube_id && track.audio_url;
    const barra = document.getElementById('barra-progresso');
    let duracao = 0;

    if (isLocal) {
        duracao = playerAudioLocal.duration || 0;
    } else {
        if (!player || !player.getDuration) return;
        duracao = player.getDuration();
    }

    if (!barra || !duracao) return;

    event.preventDefault();

    const porcentagem = calcularPorcentagemPonteiro(event, barra);
    const novoTempo = duracao * porcentagem;

    atualizarProgressoVisual(novoTempo, duracao);
    if (isLocal) {
        playerAudioLocal.currentTime = novoTempo;
    } else {
        player.seekTo(novoTempo, true);
    }
    sincronizarBusca(novoTempo);
}

function atualizarVolumePeloPonteiro(event) {
    const barra = document.getElementById('barra-volume');
    if (!barra) return;

    event.preventDefault();

    const porcentagem = calcularPorcentagemPonteiro(event, barra);
    const volume = Math.round(porcentagem * 100);

    if (player && player.setVolume) {
        player.setVolume(volume);
    }
    playerAudioLocal.volume = volume / 100;

    if (volume > 0) {
        if (player && player.unMute) player.unMute();
        playerAudioLocal.muted = false;
        volumeAntesDeMutar = volume;
    }

    atualizarVolumeVisual(volume);
}

function calcularPorcentagemPonteiro(event, barra) {
    const rect = barra.getBoundingClientRect();
    const posicao = (event.clientX - rect.left) / rect.width;
    return Math.max(0, Math.min(posicao, 1));
}

function alternarMudo() {
    const mutado = (player && player.isMuted && player.isMuted()) || playerAudioLocal.muted || playerAudioLocal.volume === 0;

    if (mutado) {
        const volume = volumeAntesDeMutar || 100;
        if (player && player.unMute) player.unMute();
        if (player && player.setVolume) player.setVolume(volume);
        playerAudioLocal.muted = false;
        playerAudioLocal.volume = volume / 100;
        atualizarVolumeVisual(volume);
    } else {
        volumeAntesDeMutar = playerAudioLocal.volume ? Math.round(playerAudioLocal.volume * 100) : (player && player.getVolume ? player.getVolume() : 100);
        if (player && player.mute) player.mute();
        playerAudioLocal.muted = true;
        playerAudioLocal.volume = 0;
        atualizarVolumeVisual(0);
    }
}

function atualizarVolumeVisual(volume) {
    const nivel = document.getElementById('nivel-volume');
    const controle = document.getElementById('controle-volume');
    const icone = document.getElementById('icone-volume');
    const volumeLimitado = Math.min(Math.max(volume, 0), 100);

    if (nivel) nivel.style.width = `${volumeLimitado}%`;
    if (controle) controle.style.left = `${volumeLimitado}%`;
    if (!icone) return;

    if (volumeLimitado === 0) {
        icone.innerText = 'volume_off';
    } else if (volumeLimitado < 50) {
        icone.innerText = 'volume_down';
    } else {
        icone.innerText = 'volume_up';
    }
}

function formatarTempo(segundos) {
    const valor = Math.floor(segundos || 0);
    const minutos = Math.floor(valor / 60);
    const resto = String(valor % 60).padStart(2, '0');
    return `${minutos}:${resto}`;
}
