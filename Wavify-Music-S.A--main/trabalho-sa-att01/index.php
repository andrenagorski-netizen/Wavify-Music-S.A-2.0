<?php
$erro_login = trim($_GET['erro'] ?? '');
?>
<!DOCTYPE html>
<html class="dark" lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>WAVIFY - Entrar</title>
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
                    },
                    fontFamily: {
                        "headline-sm": ["Syne"],
                        "body-md": ["DM Sans"],
                        "label-lg": ["DM Sans"],
                        "headline-xl": ["Syne"],
                        "headline-md": ["Syne"]
                    },
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

        .login-card {
            background: rgba(31, 31, 37, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-group:focus-within .input-icon {
            color: #d2bbff;
        }
        
        .floating-label-input:focus ~ label,
        .floating-label-input:not(:placeholder-shown) ~ label {
            transform: translateY(-1.5rem) scale(0.85);
            color: #d2bbff;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen mesh-bg flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo Section -->
        <div class="flex flex-col items-center mb-10">
            <div class="w-16 h-16 bg-primary-container rounded-2xl flex items-center justify-center shadow-[0_0_30px_rgba(124,58,237,0.4)] mb-4">
                <span class="material-symbols-outlined text-on-primary-container text-4xl" style="font-variation-settings: 'FILL' 1;">graphic_eq</span>
            </div>
            <h1 class="font-headline-xl text-4xl font-bold text-primary tracking-tighter">WAVIFY</h1>
            <p class="text-on-surface-variant/70 uppercase tracking-widest text-[10px] font-bold">Experiência de Áudio Premium</p>
        </div>

        <!-- Login Card -->
        <div class="login-card rounded-[2rem] p-10 relative overflow-hidden group">
            <!-- Decorative Light -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-primary/10 rounded-full blur-3xl group-hover:bg-primary/20 transition-colors duration-500"></div>
            
            <h2 class="font-headline-md text-2xl text-on-surface mb-2 font-bold">Bem-vindo de volta</h2>
            <p class="text-on-surface-variant font-body-sm mb-8">Por favor, insira seus dados para entrar.</p>

            <?php if ($erro_login !== ''): ?>
                <div class="mb-6 rounded-2xl border border-error/30 bg-error-container/30 px-4 py-3 text-error font-body-sm">
                    <?= htmlspecialchars($erro_login) ?>
                </div>
            <?php endif; ?>

            <form id="login-form" action="php/login.php" method="POST" class="space-y-6" autocomplete="on">
                <!-- Username -->
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Nome de usuário</label>
                    <div class="relative group input-group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 input-icon transition-colors">person</span>
                        <input id="login-username" name="usuario" type="text" required autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="seu_usuario" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface placeholder:text-on-surface-variant/20 focus:ring-2 focus:ring-primary/50 focus:bg-white/10 transition-all outline-none font-body-md" />
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="block text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60 ml-1">Gmail / Email</label>
                    <div class="relative group input-group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 input-icon transition-colors">mail</span>
                        <input id="login-email" name="email" type="email" required autocomplete="email" placeholder="alex@gmail.com" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface placeholder:text-on-surface-variant/20 focus:ring-2 focus:ring-primary/50 focus:bg-white/10 transition-all outline-none font-body-md" />
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label class="text-[10px] uppercase tracking-[0.2em] font-bold text-on-surface-variant/60">Senha</label>
                        <a href="#" class="text-[10px] text-primary hover:underline font-bold uppercase tracking-wider">Esqueceu?</a>
                    </div>
                    <div class="relative group input-group">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 input-icon transition-colors">lock</span>
                        <input id="login-password" type="password" name="senha" required autocomplete="current-password" placeholder="••••••••" class="w-full bg-white/5 border border-white/5 rounded-2xl py-4 pl-12 pr-4 text-on-surface placeholder:text-on-surface-variant/20 focus:ring-2 focus:ring-primary/50 focus:bg-white/10 transition-all outline-none font-body-md" />
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-on-primary-fixed font-bold py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-[0_10px_25px_rgba(124,58,237,0.3)] hover:shadow-[0_15px_35px_rgba(124,58,237,0.5)] mt-4">
                    Entrar
                </button>
            </form>

            <!-- Secondary Actions -->
            <div class="mt-8 pt-8 border-t border-white/5 text-center">
                <p class="text-on-surface-variant font-body-sm">
                    Não tem uma conta? 
                    <a href="wavify_signup.php" class="text-primary font-bold hover:underline">Criar conta</a>
                </p>
            </div>
        </div>

        <!-- Decorative Footer -->
        <div class="mt-10 text-center">
            <p class="text-on-surface-variant/40 text-[10px] uppercase tracking-widest">© 2026 WAVIFY DIGITAL • TODOS OS DIREITOS RESERVADOS</p>
        </div>
    </div>

</body>
</html>
