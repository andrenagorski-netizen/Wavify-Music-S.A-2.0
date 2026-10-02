<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

// Get user's own musics for initial player state
$musicas = user_musics($usuario_id);

$erro = trim($_GET['erro'] ?? '');
$sucesso = trim($_GET['sucesso'] ?? '');

// Start page rendering
render_page_start('WAVIFY - Enviar Som', 'wavify_upload.php', $usuario, $musicas);
?>

<div class="max-w-3xl mx-auto">
    <!-- Hero Header -->
    <div class="text-center mb-12">
        <h2 class="page-title mb-4 drop-shadow-[0_0_20px_rgba(210,187,255,0.2)]">Carregue suas Faixas</h2>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Carregue arquivos de áudio de alta fidelidade para tocar em qualquer lugar.</p>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="mb-6 rounded-2xl border border-error/30 bg-error-container/30 px-6 py-4 text-error font-body-md">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <div class="mb-6 rounded-2xl border border-primary/30 bg-primary-container/30 px-6 py-4 text-primary font-body-md">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <!-- Choice Section -->
    <div class="flex gap-4 mb-8 bg-surface-container/60 p-2 rounded-2xl border border-white/5">
        <button id="btn-select-single" class="flex-1 py-4 font-bold rounded-xl text-center bg-gradient-to-r from-primary to-secondary text-on-primary-fixed shadow-md transition-all duration-300" onclick="mudarTipoUpload('single')">
            Música Individual
        </button>
        <button id="btn-select-album" class="flex-1 py-4 font-bold rounded-xl text-center text-on-surface-variant hover:text-on-surface transition-all duration-300" onclick="mudarTipoUpload('album')">
            Álbum / Pasta de Músicas
        </button>
    </div>

    <!-- Upload Form -->
    <form action="php/salvar_upload.php" method="POST" enctype="multipart/form-data" class="bg-surface-container/60 backdrop-blur-2xl p-8 rounded-3xl border border-white/5 shadow-2xl space-y-6 relative overflow-hidden">
        <!-- Decorative background glow -->
        <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>

        <input type="hidden" name="tipo_upload" id="tipo_upload_field" value="single">

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Cover Art Input -->
            <div class="w-full md:w-1/3 flex flex-col gap-4">
                <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Imagem de Capa</label>
                <div class="aspect-square bg-surface border border-white/10 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden group cursor-pointer hover:border-primary/50 transition-colors">
                    <img id="cover-preview-img" alt="Pré-visualização" class="hidden absolute inset-0 w-full h-full object-cover z-0">
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant group-hover:text-primary transition-colors z-10">add_photo_alternate</span>
                    <p class="font-label-md text-label-md text-on-surface-variant mt-2 z-10 group-hover:text-primary">Carregar Capa</p>
                    <input type="file" name="capa" id="capa_upload" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                </div>
            </div>

            <!-- Metadata Fields -->
            <div class="w-full md:w-2/3 space-y-4">
                <!-- Single Audio File (Only for single upload) -->
                <div class="space-y-2" id="single-audio-wrapper">
                    <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Arquivo de Áudio</label>
                    <div class="relative flex items-center justify-center border border-dashed border-white/20 hover:border-primary/50 rounded-2xl p-6 transition-colors group cursor-pointer">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant mr-3 group-hover:text-primary">audiotrack</span>
                        <span class="font-body-md text-on-surface-variant" id="audio-filename-display">Escolher arquivo MP3/WAV...</span>
                        <input type="file" name="musica" id="audio_upload" accept="audio/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                    </div>
                </div>

                <!-- Multiple Audio Files (Only for album upload) -->
                <div class="space-y-2 hidden" id="album-audio-wrapper">
                    <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Arquivos de Áudio do Álbum</label>
                    <div class="relative flex items-center justify-center border border-dashed border-white/20 hover:border-primary/50 rounded-2xl p-6 transition-colors group cursor-pointer">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant mr-3 group-hover:text-primary">folder_open</span>
                        <span class="font-body-md text-on-surface-variant" id="album-filenames-display">Selecionar múltiplos arquivos de áudio...</span>
                        <!-- Accept multiple files or folder upload -->
                        <input type="file" name="musicas[]" id="album_upload" accept="audio/*" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>
                </div>

                <!-- Song Title (Only for single upload) -->
                <div class="space-y-2" id="single-title-wrapper">
                    <label for="titulo" class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Título da Música</label>
                    <input type="text" name="titulo" id="titulo" placeholder="ex: Midnight City" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md">
                </div>

                <!-- Artist -->
                <div class="space-y-2">
                    <label for="artista" class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Nome do Artista</label>
                    <input type="text" name="artista" id="artista" placeholder="Nome do Artista ou Banda" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Genre -->
                    <div class="space-y-2">
                        <label for="genero" class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Gênero</label>
                        <select name="genero" id="genero" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3.5 px-4 rounded-xl text-on-surface transition-all font-body-md appearance-none">
                            <option value="Electronic">Electronic</option>
                            <option value="Hip Hop">Hip-Hop</option>
                            <option value="Pop">Pop</option>
                            <option value="R&B">R&B</option>
                            <option value="Rock">Rock</option>
                        </select>
                    </div>

                    <!-- Album Name -->
                    <div class="space-y-2">
                        <label for="album" class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block" id="album-label">Nome do Álbum (Opcional)</label>
                        <input type="text" name="album" id="album" placeholder="Nome do Álbum" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-white/10">
            <button type="submit" class="bg-gradient-to-r from-primary to-secondary text-on-primary-fixed font-bold px-10 py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-[0_10px_25px_rgba(124,58,237,0.3)] flex items-center gap-2">
                <span class="material-symbols-outlined">cloud_upload</span> Publicar Som
            </button>
        </div>
    </form>
</div>

<script>
function mudarTipoUpload(tipo) {
    document.getElementById('tipo_upload_field').value = tipo;

    const btnSingle = document.getElementById('btn-select-single');
    const btnAlbum = document.getElementById('btn-select-album');

    const wrapperSingle = document.getElementById('single-audio-wrapper');
    const wrapperAlbum = document.getElementById('album-audio-wrapper');
    const wrapperTitle = document.getElementById('single-title-wrapper');
    
    const inputSingle = document.getElementById('audio_upload');
    const inputAlbum = document.getElementById('album_upload');
    const inputTitle = document.getElementById('titulo');
    const inputAlbumName = document.getElementById('album');

    if (tipo === 'single') {
        btnSingle.className = "flex-1 py-4 font-bold rounded-xl text-center bg-gradient-to-r from-primary to-secondary text-on-primary-fixed shadow-md transition-all duration-300";
        btnAlbum.className = "flex-1 py-4 font-bold rounded-xl text-center text-on-surface-variant hover:text-on-surface transition-all duration-300";
        
        wrapperSingle.classList.remove('hidden');
        wrapperTitle.classList.remove('hidden');
        wrapperAlbum.classList.add('hidden');
        
        inputSingle.required = true;
        inputAlbum.required = false;
        inputTitle.required = true;

        document.getElementById('album-label').innerText = "Nome do Álbum (Opcional)";
        inputAlbumName.placeholder = "Nome do Álbum (Opcional)";
        inputAlbumName.required = false;
    } else {
        btnSingle.className = "flex-1 py-4 font-bold rounded-xl text-center text-on-surface-variant hover:text-on-surface transition-all duration-300";
        btnAlbum.className = "flex-1 py-4 font-bold rounded-xl text-center bg-gradient-to-r from-primary to-secondary text-on-primary-fixed shadow-md transition-all duration-300";
        
        wrapperSingle.classList.add('hidden');
        wrapperTitle.classList.add('hidden');
        wrapperAlbum.classList.remove('hidden');
        
        inputSingle.required = false;
        inputAlbum.required = true;
        inputTitle.required = false;

        document.getElementById('album-label').innerText = "Nome do Álbum";
        inputAlbumName.placeholder = "Nome do Álbum (Obrigatório)";
        inputAlbumName.required = true;
    }
}

// Cover image preview
document.getElementById('capa_upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('cover-preview-img');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            preview.src = event.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
    }
});

// Single filename display
document.getElementById('audio_upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const display = document.getElementById('audio-filename-display');
    const titleInput = document.getElementById('titulo');

    if (file) {
        display.innerText = file.name;
        display.classList.remove('text-on-surface-variant');
        display.classList.add('text-primary');

        // Autofill title input with cleaned file name
        if (titleInput.value === '') {
            let nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.'));
            nameWithoutExt = nameWithoutExt.replace(/[-_]/g, ' ');
            titleInput.value = nameWithoutExt.replace(/\b\w/g, c => c.toUpperCase());
        }
    } else {
        display.innerText = "Escolher arquivo MP3/WAV...";
        display.classList.add('text-on-surface-variant');
        display.classList.remove('text-primary');
    }
});

// Album filenames display
document.getElementById('album_upload').addEventListener('change', function(e) {
    const files = e.target.files;
    const display = document.getElementById('album-filenames-display');
    if (files.length > 0) {
        display.innerText = `${files.length} arquivos selecionados`;
        display.classList.remove('text-on-surface-variant');
        display.classList.add('text-primary');
    } else {
        display.innerText = "Selecionar múltiplos arquivos de áudio...";
        display.classList.add('text-on-surface-variant');
        display.classList.remove('text-primary');
    }
});
</script>

<?php
render_page_end($usuario, $musicas);
?>
