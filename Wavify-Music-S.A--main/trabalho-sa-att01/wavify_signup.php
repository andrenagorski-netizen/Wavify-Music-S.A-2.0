<?php
$erro_cadastro = trim($_GET['erro'] ?? '');
?>
<!DOCTYPE html>
<html class="dark" lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>WAVIFY - Criar Conta</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;500;700&amp;family=Syne:wght@600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-variant": "#35343a",
                        "on-secondary-fixed": "#2a1700",
                        "on-primary-fixed-variant": "#5a00c6",
                        "on-tertiary-fixed": "#0f1c2c",
                        "surface-container-lowest": "#0e0e13",
                        "secondary-fixed-dim": "#ffb95f",
                        "on-secondary-container": "#5b3800",
                        "error": "#ffb4ab",
                        "surface-dim": "#131318",
                        "secondary": "#ffb95f",
                        "inverse-surface": "#e4e1e9",
                        "surface-bright": "#39383e",
                        "inverse-on-surface": "#303036",
                        "on-secondary-fixed-variant": "#653e00",
                        "on-error": "#690005",
                        "primary-fixed": "#eaddff",
                        "surface-container-low": "#1b1b20",
                        "primary": "#d2bbff",
                        "surface-container-highest": "#35343a",
                        "error-container": "#93000a",
                        "primary-container": "#7c3aed",
                        "on-surface": "#e4e1e9",
                        "primary-fixed-dim": "#d2bbff",
                        "tertiary-container": "#5a687a",
                        "inverse-primary": "#732ee4",
                        "tertiary-fixed": "#d6e4f9",
                        "surface": "#131318",
                        "on-primary-container": "#ede0ff",
                        "surface-container-high": "#2a292f",
                        "outline": "#958da1",
                        "on-surface-variant": "#ccc3d8",
                        "secondary-fixed": "#ffddb8",
                        "on-background": "#e4e1e9",
                        "tertiary": "#bac8dc",
                        "on-primary-fixed": "#25005a",
                        "on-tertiary-container": "#d9e7fc",
                        "surface-tint": "#d2bbff",
                        "outline-variant": "#4a4455",
                        "tertiary-fixed-dim": "#bac8dc",
                        "on-tertiary-fixed-variant": "#3a4859",
                        "on-primary": "#3f008e",
                        "on-tertiary": "#243141",
                        "secondary-container": "#ee9800",
                        "on-error-container": "#ffdad6",
                        "background": "#131318",
                        "on-secondary": "#472a00",
                        "surface-container": "#1f1f25"
                    }
                }
            }
        }
    </script>
    <style>
        .mesh-bg {
            background: linear-gradient(-45deg, #0A0A0F, #131318, #0D1B2A, #1a1025);
            background-size: 400% 400%;
            animation: mesh 15s ease infinite;
        }
        @keyframes mesh {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .signup-card {
            background: rgba(31, 31, 37, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen mesh-bg flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 bg-primary-container rounded-2xl flex items-center justify-center shadow-[0_0_30px_rgba(124,58,237,0.4)] mb-4">
                <span class="material-symbols-outlined text-on-primary-container text-4xl" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
            </div>
            <h1 class="font-headline-xl text-4xl font-bold text-primary tracking-tighter">WAVIFY</h1>
        </div>

        <!-- Signup Card -->
        <div class="signup-card rounded-[2rem] p-8 md:p-10 relative overflow-hidden">
            <h2 class="font-headline-md text-2xl text-on-surface mb-2 font-bold">Criar Nova Conta</h2>
            <p class="text-on-surface-variant font-body-sm mb-8">Junte-se à comunidade premium do Wavify.</p>

            <?php if ($erro_cadastro !== ''): ?>
                <div class="mb-6 rounded-2xl border border-error/30 bg-error-container/30 px-4 py-3 text-error font-body-sm">
                    <?= htmlspecialchars($erro_cadastro) ?>
                </div>
            <?php endif; ?>

            <form id="signup-form" action="php/cadastro.php" method="POST" class="space-y-4" autocomplete="off" data-lpignore="true" data-form-type="other">
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Nome de Usuário</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40">person</span>
                        <input id="signup-username" name="usuario" type="text" required autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="seu_usuario" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface focus:ring-2 focus:ring-primary/50 outline-none transition-all" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Gmail / Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40">mail</span>
                        <input id="signup-email" name="email" type="email" required autocomplete="off" inputmode="email" autocapitalize="none" spellcheck="false" placeholder="exemplo@gmail.com" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface focus:ring-2 focus:ring-primary/50 outline-none transition-all" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Senha</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40">lock</span>
                        <input id="signup-password" name="senha" type="password" required autocomplete="new-password" placeholder="••••••••" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface focus:ring-2 focus:ring-primary/50 outline-none transition-all" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Confirmar Senha</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40">verified_user</span>
                        <input id="signup-confirm" name="confirma_senha" type="password" required autocomplete="new-password" placeholder="••••••••" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface focus:ring-2 focus:ring-primary/50 outline-none transition-all" />
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-on-primary-fixed font-bold py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-[0_10px_25px_rgba(124,58,237,0.3)] mt-6">
                    Criar Conta
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-white/5 text-center">
                <p class="text-on-surface-variant font-body-sm">
                    Já tem uma conta? 
                    <a href="index.php" class="text-primary font-bold hover:underline">Entrar agora</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const signupForm = document.getElementById('signup-form');
        const signupUsernameInput = document.getElementById('signup-username');
        const signupEmailInput = document.getElementById('signup-email');
        const signupPasswordInput = document.getElementById('signup-password');
        const signupConfirmInput = document.getElementById('signup-confirm');

        [signupUsernameInput, signupEmailInput].forEach((input) => {
            input.dataset.typedValue = '';

            input.addEventListener('input', function() {
                if (document.activeElement === this) {
                    this.dataset.typedValue = this.value;
                    this.dataset.wasTyped = 'true';
                }
            });
        });

        function restoreTypedSignupFields() {
            [signupUsernameInput, signupEmailInput].forEach((input) => {
                if (input.dataset.wasTyped === 'true' && input.value !== input.dataset.typedValue) {
                    input.value = input.dataset.typedValue;
                }
            });
        }

        [signupPasswordInput, signupConfirmInput].forEach((input) => {
            input.addEventListener('focus', function() {
                setTimeout(restoreTypedSignupFields, 50);
            });
        });

        signupForm.addEventListener('submit', restoreTypedSignupFields, true);

document.getElementById('signup-form').addEventListener('submit', function(e) {

const senha = document.getElementById('signup-password').value;

const confirmar = document.getElementById('signup-confirm').value;


if (senha !== confirmar) {


e.preventDefault();


alert('As senhas não coincidem!');

}


});
    </script>
</body>
</html>
