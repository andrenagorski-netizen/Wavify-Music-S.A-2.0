<?php
require_once 'php/layout.php';
ensure_auth();

$usuario = current_user();
$usuario_id = (int) $usuario['id'];

global $conexao;

// Fetch profile photo
$foto_stmt = mysqli_prepare($conexao, "SELECT foto FROM usuarios WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($foto_stmt, "i", $usuario_id);
mysqli_stmt_execute($foto_stmt);
$foto_res = mysqli_stmt_get_result($foto_stmt);
$row_foto = mysqli_fetch_assoc($foto_res);
$user_foto = $row_foto['foto'] ?? '';
if ($user_foto !== '') {
    $_SESSION['wavify_user_img'] = $user_foto;
}

// Fetch user stats
$songs_count_res = mysqli_query($conexao, "SELECT COUNT(*) as total FROM musicas WHERE usuario_id = '$usuario_id'");
$songs_count = mysqli_fetch_assoc($songs_count_res)['total'] ?? 0;

$playlists_count_res = mysqli_query($conexao, "SELECT COUNT(*) as total FROM playlists WHERE usuario_id = '$usuario_id'");
$playlists_count = mysqli_fetch_assoc($playlists_count_res)['total'] ?? 0;

$recent_count_res = mysqli_query($conexao, "SELECT COUNT(*) as total FROM reproducoes_recentes WHERE usuario_id = '$usuario_id'");
$recent_count = mysqli_fetch_assoc($recent_count_res)['total'] ?? 0;

// Fetch initial tracks for footer player
$musicas = user_musics($usuario_id);

$erro = trim($_GET['erro'] ?? '');
$sucesso = trim($_GET['sucesso'] ?? '');

// Start page rendering
render_page_start('WAVIFY - Perfil', 'wavify_profile.php', $usuario, $musicas);
?>

<div class="max-w-4xl mx-auto pb-10">
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

    <div class="profile-glow bg-surface-container-low/40 backdrop-blur-2xl rounded-3xl p-8 md:p-12 border border-white/5 relative overflow-hidden">
        <!-- Decorative light -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>

        <h2 class="font-headline-lg text-headline-lg text-on-surface mb-8 tracking-tight">Configurações de Perfil</h2>
        
        <form id="profile-form" action="php/salvar_perfil.php" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-12">
            <!-- Profile Picture Section -->
            <div class="flex flex-col items-center gap-6 shrink-0">
                <div class="relative group">
                    <div class="w-48 h-48 rounded-full overflow-hidden border-2 border-primary/30 p-1 bg-background shadow-2xl group-hover:border-primary transition-colors duration-500">
                        <img id="profile-preview-img" alt="Foto de perfil" class="w-full h-full rounded-full object-cover" src="<?= htmlspecialchars($user_foto !== '' ? $user_foto : 'https://i.pravatar.cc/120?img=12') ?>"/>
                    </div>
                    <label for="profile-upload" class="absolute inset-0 bg-black/60 rounded-full flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer backdrop-blur-sm">
                        <span class="material-symbols-outlined text-primary text-4xl mb-2">add_a_photo</span>
                        <span class="text-white text-xs font-label-md uppercase tracking-widest">Alterar Foto</span>
                        <input id="profile-upload" name="foto_upload" type="file" accept="image/*" class="hidden" />
                    </label>
                </div>
                <div class="text-center">
                    <h3 class="font-headline-sm text-headline-sm text-white mb-2"><?= htmlspecialchars($usuario['usuario']) ?></h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant/70 uppercase tracking-wider">Membro Premium WAVIFY</p>
                </div>
            </div>

            <!-- Profile Info Section -->
            <div class="flex-1 space-y-8">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <h3 class="font-label-lg text-label-lg text-primary border-b border-white/10 pb-2 uppercase tracking-widest">Informações Pessoais</h3>
                    
                    <div class="space-y-2">
                        <label for="user-name" class="block font-body-sm text-body-sm text-on-surface-variant">Nome de Usuário</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/50 group-focus-within:text-primary transition-colors">person</span>
                            <input id="user-name" name="usuario" type="text" value="<?= htmlspecialchars($usuario['usuario']) ?>" required class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-4 pl-12 pr-4 rounded-xl text-on-surface transition-all font-body-md" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="user-email-input" class="block font-body-sm text-body-sm text-on-surface-variant">Endereço de E-mail</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/50 group-focus-within:text-primary transition-colors">mail</span>
                            <input id="user-email-input" name="email" type="email" value="<?= htmlspecialchars($usuario['email']) ?>" required class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-4 pl-12 pr-4 rounded-xl text-on-surface transition-all font-body-md" />
                        </div>
                    </div>
                </div>

                <!-- Password Changes -->
                <div class="space-y-4 pt-4 border-t border-white/5">
                    <h3 class="font-label-lg text-label-lg text-primary border-b border-white/10 pb-2 uppercase tracking-widest">Segurança (Mudar Senha)</h3>
                    
                    <div class="space-y-2">
                        <label for="senha_atual" class="block font-body-sm text-body-sm text-on-surface-variant">Senha Atual</label>
                        <input id="senha_atual" name="senha_atual" type="password" placeholder="Digite a senha atual" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="nova_senha" class="block font-body-sm text-body-sm text-on-surface-variant">Nova Senha</label>
                            <input id="nova_senha" name="nova_senha" type="password" placeholder="Nova senha" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md" />
                        </div>
                        <div class="space-y-2">
                            <label for="confirmar_senha" class="block font-body-sm text-body-sm text-on-surface-variant">Confirmar Nova Senha</label>
                            <input id="confirmar_senha" name="confirmar_senha" type="password" placeholder="Confirmar nova senha" class="w-full bg-white/5 border border-white/5 focus:border-primary/50 focus:ring-2 focus:ring-primary/20 outline-none py-3 px-4 rounded-xl text-on-surface transition-all font-body-md" />
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="pt-6 flex flex-col sm:flex-row gap-4">
                    <button class="flex-1 bg-gradient-to-r from-primary to-secondary text-on-primary-fixed font-bold py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-[0_10px_25px_rgba(124,58,237,0.3)] hover:shadow-[0_15px_35px_rgba(124,58,237,0.5)] flex items-center justify-center gap-2" type="submit">
                        <span class="material-symbols-outlined">save</span> Salvar Alterações
                    </button>
                    <a class="flex-1 bg-white/5 border border-white/10 text-on-surface font-bold py-4 rounded-2xl hover:bg-white/10 text-center transition-all flex items-center justify-center gap-2 text-error hover:bg-error/10" href="php/logout.php">
                        <span class="material-symbols-outlined">logout</span> Sair da Conta
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Account Stats Section -->
    <div class="grid grid-cols-3 gap-6 mt-8">
        <div class="bg-surface-container-low/40 backdrop-blur-2xl border border-white/5 rounded-2xl p-6 text-center shadow-xl">
            <span class="material-symbols-outlined text-primary text-4xl mb-2">library_music</span>
            <h4 class="font-headline-sm text-2xl font-bold text-white"><?= $songs_count ?></h4>
            <p class="text-xs text-on-surface-variant/80 uppercase tracking-widest mt-1">Músicas Salvas</p>
        </div>
        <div class="bg-surface-container-low/40 backdrop-blur-2xl border border-white/5 rounded-2xl p-6 text-center shadow-xl">
            <span class="material-symbols-outlined text-secondary text-4xl mb-2">playlist_play</span>
            <h4 class="font-headline-sm text-2xl font-bold text-white"><?= $playlists_count ?></h4>
            <p class="text-xs text-on-surface-variant/80 uppercase tracking-widest mt-1">Playlists</p>
        </div>
        <div class="bg-surface-container-low/40 backdrop-blur-2xl border border-white/5 rounded-2xl p-6 text-center shadow-xl">
            <span class="material-symbols-outlined text-tertiary text-4xl mb-2">history</span>
            <h4 class="font-headline-sm text-2xl font-bold text-white"><?= $recent_count ?></h4>
            <p class="text-xs text-on-surface-variant/80 uppercase tracking-widest mt-1">Reproduzidas</p>
        </div>
    </div>
</div>

<script>
document.getElementById('profile-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profile-preview-img').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php
render_page_end($usuario, $musicas);
?>
