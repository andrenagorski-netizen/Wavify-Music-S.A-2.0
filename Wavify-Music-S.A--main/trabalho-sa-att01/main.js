document.addEventListener('DOMContentLoaded', () => {
    // --- 0. Authentication Guard ---
    const sessionUser = window.wavifyUsuario || null;

    if (sessionUser) {
        localStorage.setItem('wavify_logged_in', 'true');
        if (sessionUser.nome) localStorage.setItem('wavify_user_name', sessionUser.nome);
        if (sessionUser.email) localStorage.setItem('wavify_user_email', sessionUser.email);
    }

    const isLoggedIn = localStorage.getItem('wavify_logged_in') === 'true';
    const isLoginPage = window.location.pathname.includes('index.php') ||
                        window.location.pathname.endsWith('/');

    if (!isLoggedIn && !isLoginPage) {
        window.location.href = 'index.php';
        return;
    }

    // --- 1. SPA PJAX Dynamic Router ---
    const setupSPARouting = () => {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            // Skip non-local, action, or logout anchors
            if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('http') && !href.startsWith(window.location.origin)) {
                return;
            }

            if (href.includes('logout.php') || href.includes('index.php') || href.includes('signup.php')) {
                return;
            }

            e.preventDefault();
            window.navegarSPA(href);
        });

        window.addEventListener('popstate', () => {
            window.navegarSPA(window.location.href);
        });
    };

    window.navegarSPA = (href) => {
        exibirLoadingProgress(true);

        fetch(href)
            .then(res => {
                if (!res.ok) throw new Error("Erro ao buscar página");
                return res.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Update main content container
                const newMain = doc.querySelector('main');
                const currentMain = document.querySelector('main');
                if (newMain && currentMain) {
                    currentMain.innerHTML = newMain.innerHTML;
                }

                // Update document title
                document.title = doc.title || 'WAVIFY';

                // Sync sidebar active links
                atualizarLinksSidebarAtivos(href);

                // Update address bar
                history.pushState(null, '', href);

                // Close mobile menu
                fecharMenuCelular();

                // Re-evaluate script tags inside the new content
                if (currentMain) {
                    const scripts = currentMain.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        if (oldScript.src) {
                            newScript.src = oldScript.src;
                        } else {
                            newScript.textContent = oldScript.textContent;
                        }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }

                // Parse and execute scripts that define window.wavifyMusicas / window.wavifyUsuario from the fetched doc
                const newScripts = doc.querySelectorAll('script');
                newScripts.forEach(script => {
                    if (script.textContent.includes('window.wavifyMusicas') || script.textContent.includes('window.wavifyUsuario')) {
                        try {
                            eval(script.textContent);
                        } catch (e) {
                            console.error("Erro ao avaliar script de dados do SPA:", e);
                        }
                    }
                });

                // Sync local songs list for the active page
                if (Array.isArray(window.wavifyMusicas)) {
                    console.log("PLAYLIST ATUAL (SPA SAFE):", window.wavifyMusicas);
                }

                // Atualiza a playlist usada pelo player por referência (sem resetar)
                if (typeof playlistInicial !== 'undefined' && Array.isArray(window.wavifyMusicas)) {
                    playlistInicial.splice(0, playlistInicial.length);
                    window.wavifyMusicas.forEach(m => playlistInicial.push(m));
                }

                // Sincroniza a interface do player atual (por exemplo, os elementos do Hero se mudou para a Home)
                if (typeof trackAtual !== 'undefined' && trackAtual) {
                    if (typeof atualizarInterfaceMusica === 'function') {
                        atualizarInterfaceMusica(trackAtual);
                    }
                }
                if (typeof atualizarIconePlayPause === 'function') {
                    atualizarIconePlayPause();
                }

                vincularEventosPaginaAtiva(href);
                exibirLoadingProgress(false);
            })
            .catch(err => {
                console.error("SPA Fail fallback: ", err);
                exibirLoadingProgress(false);
                window.location.href = href;
            });
    };

    const atualizarLinksSidebarAtivos = (href) => {
        const filename = href.split('/').pop() || 'wavify_home.php';
        const sidebar = document.getElementById('app-sidebar');
        if (!sidebar) return;

        const links = sidebar.querySelectorAll('nav a, div.border-t a, .sidebar-bottom a');
        links.forEach(link => {
            const linkHref = link.getAttribute('href');
            const linkOn = linkHref === filename;

            if (linkOn) {
                link.className = "relative flex items-center gap-4 px-4 py-3 rounded-full bg-gradient-to-r from-primary to-secondary text-on-primary-fixed transition-all font-bold";
                const icon = link.querySelector('.material-symbols-outlined');
                if (icon) icon.style.fontVariationSettings = "'FILL' 1";
            } else {
                link.className = "relative flex items-center gap-4 px-4 py-3 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-white/5 transition-all font-bold";
                const icon = link.querySelector('.material-symbols-outlined');
                if (icon) icon.style.fontVariationSettings = "'FILL' 0";
            }
        });
    };

    const exibirLoadingProgress = (exibir) => {
        let loader = document.getElementById('spa-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'spa-loader';
            loader.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:linear-gradient(to right, #d2bbff, #ffb95f);z-index:9999;transition:width 0.4s ease, opacity 0.4s ease;width:0%;opacity:0;';
            document.body.appendChild(loader);
        }

        if (exibir) {
            loader.style.opacity = '1';
            loader.style.width = '30%';
            setTimeout(() => {
                if (loader.style.width === '30%') {
                    loader.style.width = '70%';
                }
            }, 300);
        } else {
            loader.style.width = '100%';
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.width = '0%';
                }, 400);
            }, 200);
        }
    };

    let menuAberto = false;

    const inicializarMenuCelular = () => {
        const btn = document.getElementById('mobile-menu-button');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        const sidebar = document.getElementById('app-sidebar');
        const topbar = document.getElementById('topbar');

        if (!btn || !sidebar || !backdrop) return;

        // Remove listener antigo se houver
        const novoBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(novoBtn, btn);

        novoBtn.addEventListener('click', () => {
            if (window.innerWidth >= 768) {
                // Desktop Toggle (Collapse/Expand)
                const isCollapsed = document.documentElement.classList.contains('menu-collapsed');
                if (isCollapsed) {
                    document.documentElement.classList.remove('menu-collapsed');
                    localStorage.setItem('wavify_menu_collapsed', 'false');
                } else {
                    document.documentElement.classList.add('menu-collapsed');
                    localStorage.setItem('wavify_menu_collapsed', 'true');
                }
            } else {
                // Mobile Drawer Toggle
                menuAberto = !menuAberto;
                
                const bar1 = document.getElementById('bar1');
                const bar2 = document.getElementById('bar2');
                const bar3 = document.getElementById('bar3');

                if (menuAberto) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    
                    backdrop.classList.remove('hidden');
                    backdrop.classList.add('flex', 'md:hidden');
                    
                    // Animação do X
                    if(bar1) {
                        bar1.classList.add('rotate-45', 'translate-y-[6px]');
                        bar2.classList.add('opacity-0', 'scale-x-0');
                        bar3.classList.add('-rotate-45', '-translate-y-[6px]');
                    }
                } else {
                    fecharMenuCelular();
                }
            }
        });

        backdrop.addEventListener('click', () => {
            fecharMenuCelular();
        });
    };

    const fecharMenuCelular = () => {
        if (window.innerWidth >= 768) return; // Do not close on desktop

        const backdrop = document.getElementById('mobile-menu-backdrop');
        const sidebar = document.getElementById('app-sidebar');
        const bar1 = document.getElementById('bar1');
        const bar2 = document.getElementById('bar2');
        const bar3 = document.getElementById('bar3');
        
        menuAberto = false;

        if (sidebar && backdrop) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0'); 
            backdrop.classList.add('hidden');
            backdrop.classList.remove('flex', 'md:hidden');
        }

        if(bar1) {
            bar1.classList.remove('rotate-45', 'translate-y-[6px]');
            bar2.classList.remove('opacity-0', 'scale-x-0');
            bar3.classList.remove('-rotate-45', '-translate-y-[6px]');
        }
    };

    // --- 3. Search Autocomplete Dropdown ---
    const inicializarBuscaAutocomplete = () => {
        const searchInputs = document.querySelectorAll('input[type="search"], input[placeholder*="Buscar"], input[placeholder*="buscar"]');
        
        searchInputs.forEach(input => {
            input.setAttribute('autocomplete', 'off');
            
            // Create suggestions container
            let dropdown = document.getElementById('search-dropdown-' + (input.id || 'nav'));
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.id = 'search-dropdown-' + (input.id || 'nav');
                dropdown.className = 'absolute top-full left-0 right-0 mt-2 bg-surface-container/95 border border-white/10 rounded-2xl max-h-96 overflow-y-auto hidden shadow-2xl z-[100] p-2 backdrop-blur-xl';
                input.parentNode.appendChild(dropdown);
            }

            input.addEventListener('input', () => {
                const query = input.value.trim();
                if (query.length < 2) {
                    dropdown.classList.add('hidden');
                    return;
                }

                fetch(`php/buscar_musicas.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(tracks => {
                        if (tracks.length === 0) {
                            dropdown.innerHTML = '<div class="text-on-surface-variant p-4 text-center text-sm">Nenhuma música encontrada</div>';
                        } else {
                            dropdown.innerHTML = tracks.map((track) => `
                                <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer group" onclick="window.tocarMusicaPesquisada(${JSON.stringify(track).replace(/"/g, '&quot;')})">
                                    <img src="${track.thumbnail || 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'}" class="w-10 h-10 rounded object-cover">
                                    <div class="truncate flex-1">
                                        <span class="block font-bold text-on-surface text-sm truncate group-hover:text-primary transition-colors">${track.titulo}</span>
                                        <span class="block text-xs text-on-surface-variant truncate">${track.artista}</span>
                                    </div>
                                    <span class="material-symbols-outlined text-primary opacity-0 group-hover:opacity-100 transition-opacity text-sm">play_arrow</span>
                                </div>
                            `).join('');
                        }
                        dropdown.classList.remove('hidden');
                    })
                    .catch(err => {
                        console.error("Erro na busca: ", err);
                    });
            });

            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    };

    window.tocarMusicaPesquisada = function(track) {
        tocarMusica(
            track.youtube_id || '',
            track.titulo,
            track.artista,
            track.thumbnail,
            null,
            track.id,
            {
                playlist: [track],
                audio_url: track.audio_url || ''
            }
        );
        // Hide all search dropdowns
        document.querySelectorAll('[id^="search-dropdown-"]').forEach(d => d.classList.add('hidden'));
    };

    // --- 4. YouTube Autofill Metadatas ---
    const inicializarYoutubeAutofill = () => {
        const linkInput = document.getElementById('youtube_link');
        const titleInput = document.getElementById('youtube_title');
        const artistInput = document.getElementById('youtube_artist');

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
                            console.error("Erro ao buscar metadados do YouTube: ", err);
                        });
                }
            });
        }
    };

    // Bind page-specific elements and controls
    const vincularEventosPaginaAtiva = (href = window.location.pathname) => {
        inicializarMenuCelular();
        inicializarBuscaAutocomplete();
        inicializarYoutubeAutofill();

        // Like button actions
        const allButtons = document.querySelectorAll('button .material-symbols-outlined');
        allButtons.forEach(icon => {
            if (icon.textContent.trim() === 'favorite_border' || icon.textContent.trim() === 'favorite') {
                const button = icon.parentElement;
                // Avoid binding multiple times
                if (button.dataset.likeBound === 'true') return;
                button.dataset.likeBound = 'true';
                
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (icon.textContent.trim() === 'favorite_border') {
                        icon.textContent = 'favorite';
                        icon.style.fontVariationSettings = "'FILL' 1";
                        icon.classList.add('text-primary');
                    } else {
                        icon.textContent = 'favorite_border';
                        icon.style.fontVariationSettings = "'FILL' 0";
                        icon.classList.remove('text-primary');
                    }
                });
            }
        });
    };

    // Initialize all routing and events
    setupSPARouting();
    vincularEventosPaginaAtiva();

    // Eventos de Logout
    const logoutLinks = document.querySelectorAll('a[href="php/logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', () => {
            localStorage.removeItem('wavify_logged_in');
            localStorage.removeItem('wavify_user_name');
            localStorage.removeItem('wavify_user_email');
            // Limpa o estado do player para não tocar a música da conta anterior
            localStorage.removeItem('wavify_player_state');
            localStorage.removeItem('wavify_player_command');
        });
    });

    // --- 5. Global Toast Feedback ---
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

    // --- 6. Global Playlists Popup Menu ---
    window.openPlaylistMenu = function(event, musicaId) {
        event.stopPropagation();
        
        // Remove existing dropdowns
        const oldMenu = document.getElementById('wavify-playlist-dropdown');
        if (oldMenu) {
            oldMenu.remove();
        }
        
        // Create the dropdown container
        const dropdown = document.createElement('div');
        dropdown.id = 'wavify-playlist-dropdown';
        dropdown.className = 'absolute bg-surface-container/95 border border-white/10 rounded-2xl py-2 shadow-2xl z-[999] min-w-[200px] max-w-[260px] max-h-[300px] overflow-y-auto backdrop-blur-xl transition-all duration-300';
        
        // Position it near the button
        const button = event.currentTarget;
        const rect = button.getBoundingClientRect();
        dropdown.style.top = `${rect.bottom + window.scrollY + 8}px`;
        dropdown.style.left = `${Math.min(rect.left + window.scrollX, window.innerWidth - 220)}px`;
        
        // Fetch playlists from php/listar_playlists_json.php
        dropdown.innerHTML = '<div class="text-on-surface-variant p-3 text-center text-xs flex items-center justify-center gap-2"><span class="material-symbols-outlined animate-spin text-[16px]">sync</span> Carregando...</div>';
        document.body.appendChild(dropdown);
        
        fetch('php/listar_playlists_json.php')
            .then(res => res.json())
            .then(data => {
                if (data.sucesso && data.playlists && data.playlists.length > 0) {
                    let html = '<div class="px-3 py-1.5 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Adicionar à Playlist</div>';
                    data.playlists.forEach(p => {
                        const capa = p.capa || 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=100&auto=format&fit=crop';
                        html += `
                            <div class="flex items-center gap-3 px-3 py-2 hover:bg-white/5 cursor-pointer group text-sm" onclick="window.adicionarMusicaAPlaylistGlobal(${p.id}, ${musicaId}, '${p.nome.replace(/'/g, "\\'")}')">
                                <img src="${capa}" class="w-8 h-8 rounded object-cover">
                                <span class="truncate text-on-surface group-hover:text-primary transition-colors">${p.nome}</span>
                            </div>
                        `;
                    });
                    dropdown.innerHTML = html;
                } else {
                    dropdown.innerHTML = `
                        <div class="text-on-surface-variant p-4 text-center text-xs">
                            Nenhuma playlist encontrada.
                            <a href="wavify_playlists.php" class="block text-primary hover:underline mt-2 font-bold flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">add</span> Criar uma
                            </a>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error(err);
                dropdown.innerHTML = '<div class="text-error p-3 text-center text-xs">Erro ao carregar playlists</div>';
            });
            
        // Close dropdown when clicking outside
        const closeDropdown = (e) => {
            if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closeDropdown);
        }, 10);
    };

    window.adicionarMusicaAPlaylistGlobal = function(playlistId, musicaId, playlistNome) {
        const dropdown = document.getElementById('wavify-playlist-dropdown');
        if (dropdown) dropdown.remove();
        
        const formData = new FormData();
        formData.append('playlist_id', playlistId);
        formData.append('musica_id', musicaId);
        
        fetch('php/adicionar_musica_playlist.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                window.mostrarToastFeedback(`Adicionada à playlist "${playlistNome}"!`, 'success');
            } else {
                window.mostrarToastFeedback(data.erro || 'Erro ao adicionar à playlist.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            window.mostrarToastFeedback('Erro de conexão.', 'error');
        });
    };
});
