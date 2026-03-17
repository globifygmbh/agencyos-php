<!DOCTYPE html>
<html lang="de" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'AgencyOS') ?> – AgencyOS</title>
    <!-- Theme: apply immediately to prevent FOUC -->
    <script>
        (function() {
            var mode = localStorage.getItem('themeMode') || 'system';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var dark = mode === 'dark' || (mode === 'system' && prefersDark);
            if (!dark) {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        heading: ['Outfit', 'Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#009dde',
                            50:  '#e6f7fd',
                            100: '#b3e8f8',
                            200: '#80d9f3',
                            300: '#4dcaee',
                            400: '#26bde9',
                            500: '#009dde',
                            600: '#0088c0',
                            700: '#006d9a',
                            800: '#005274',
                            900: '#00374e',
                        },
                    },
                    backdropBlur: {
                        xs: '2px',
                    },
                }
            }
        }

        // currentUser als globale JS-Variable
        window.CURRENT_USER = <?= json_encode([
            'id'            => $currentUser['id'] ?? '',
            'first_name'    => $currentUser['first_name'] ?? '',
            'last_name'     => $currentUser['last_name'] ?? '',
            'email'         => $currentUser['email'] ?? '',
            'role'          => $currentUser['role'] ?? '',
            'profile_image' => $currentUser['profile_image'] ?? null,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function appLayout() {
            return {
                sidebarOpen: true,
                currentUser: window.CURRENT_USER,
                activeTimer: null,
                timerSeconds: 0,
                timerInterval: null,
                unreadChat: 0,
                unreadNotifications: 0,
                customLinks: [],
                showCustomLinkModal: false,
                newLink: { label: '', url: '', emoji: '🔗' },
                notifications: [],
                themeMode: localStorage.getItem('themeMode') || 'system',
                isDark: true,

                init() {
                    this.applyTheme();
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        if (this.themeMode === 'system') this.applyTheme();
                    });
                    this.loadTimerState();
                    this.loadUnreadCounts();
                    this.loadCustomLinks();
                    this.loadNotifications();
                    setInterval(() => this.loadUnreadCounts(), 30000);
                },

                applyTheme() {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (this.themeMode === 'dark') {
                        this.isDark = true;
                    } else if (this.themeMode === 'light') {
                        this.isDark = false;
                    } else {
                        this.isDark = prefersDark;
                    }
                    document.documentElement.classList.toggle('dark', this.isDark);
                    document.documentElement.classList.toggle('light', !this.isDark);
                },

                cycleTheme() {
                    const modes = ['dark', 'light', 'system'];
                    const idx = modes.indexOf(this.themeMode);
                    this.themeMode = modes[(idx + 1) % 3];
                    localStorage.setItem('themeMode', this.themeMode);
                    this.applyTheme();
                },

                themeIcon() {
                    if (this.themeMode === 'dark') return 'moon';
                    if (this.themeMode === 'light') return 'sun';
                    return 'monitor';
                },

                themeLabel() {
                    if (this.themeMode === 'dark') return 'Dunkel';
                    if (this.themeMode === 'light') return 'Hell';
                    return 'System';
                },

                loadNotifications() {
                    fetch('/api/notifications').then(r => r.json()).then(d => {
                        this.notifications = Array.isArray(d) ? d : (d.data || []);
                    }).catch(() => {});
                },

                markAllRead() {
                    fetch('/api/notifications/read-all', { method: 'POST' }).then(() => {
                        this.notifications.forEach(n => n.is_read = true);
                        this.unreadNotifications = 0;
                    }).catch(() => {});
                },

                readNotif(n) {
                    if (!n.is_read) {
                        fetch('/api/notifications/' + n.id + '/read', { method: 'POST' }).then(() => {
                            n.is_read = true;
                            this.unreadNotifications = Math.max(0, this.unreadNotifications - 1);
                        }).catch(() => {});
                    }
                    if (n.link) window.location.href = n.link;
                },

                formatNotifTime(ts) {
                    if (!ts) return '';
                    const d = new Date(ts);
                    const now = new Date();
                    const diff = Math.floor((now - d) / 1000);
                    if (diff < 60) return 'gerade eben';
                    if (diff < 3600) return Math.floor(diff / 60) + ' Min.';
                    if (diff < 86400) return Math.floor(diff / 3600) + ' Std.';
                    return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
                },

                manageCustomLinks() {
                    this.showCustomLinkModal = true;
                },

                deleteCustomLink(link) {
                    fetch('/api/settings/custom-links', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ url: link.url })
                    }).then(() => {
                        this.customLinks = this.customLinks.filter(l => l.url !== link.url);
                    }).catch(() => {});
                },

                loadTimerState() {
                    fetch('/api/time/active')
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.id) {
                                this.activeTimer = data;
                                const start = new Date(data.start_time);
                                this.timerSeconds = Math.floor((Date.now() - start.getTime()) / 1000);
                                this.startTimerTick();
                            }
                        }).catch(() => {});
                },

                startTimerTick() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.timerInterval = setInterval(() => {
                        this.timerSeconds++;
                    }, 1000);
                },

                stopTimer() {
                    if (!this.activeTimer) return;
                    fetch('/api/time/' + this.activeTimer.id + '/stop', { method: 'POST' })
                        .then(() => {
                            this.activeTimer = null;
                            this.timerSeconds = 0;
                            if (this.timerInterval) clearInterval(this.timerInterval);
                            this.timerInterval = null;
                        }).catch(() => {});
                },

                formatTimer(s) {
                    const h = Math.floor(s / 3600).toString().padStart(2, '0');
                    const m = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
                    const sec = (s % 60).toString().padStart(2, '0');
                    return h + ':' + m + ':' + sec;
                },

                loadUnreadCounts() {
                    fetch('/api/chat/unread').then(r => r.json()).then(d => { this.unreadChat = d.count || 0; }).catch(() => {});
                    fetch('/api/notifications/unread').then(r => r.json()).then(d => { this.unreadNotifications = d.count || 0; }).catch(() => {});
                },

                loadCustomLinks() {
                    fetch('/api/settings/custom-links').then(r => r.json()).then(d => { this.customLinks = d || []; }).catch(() => {});
                },

                saveCustomLink() {
                    if (!this.newLink.label || !this.newLink.url) return;
                    let url = this.newLink.url;
                    if (!url.startsWith('http://') && !url.startsWith('https://')) url = 'https://' + url;
                    const link = { label: this.newLink.label, url, emoji: this.newLink.emoji || '🔗' };
                    fetch('/api/settings/custom-links', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(link)
                    }).then(() => {
                        this.customLinks.push(link);
                        this.newLink = { label: '', url: '', emoji: '🔗' };
                        this.showCustomLinkModal = false;
                    }).catch(() => {});
                },

                toggleSidebar() { this.sidebarOpen = !this.sidebarOpen; },

                userInitials() {
                    return (this.currentUser.first_name?.charAt(0) || '') + (this.currentUser.last_name?.charAt(0) || '');
                }
            };
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #000; color: #fff; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Outfit', Inter, sans-serif; }
        .font-mono, code, pre { font-family: 'JetBrains Mono', monospace; }

        [x-cloak] { display: none !important; }

        /* Glassmorphism */
        .glass {
            background: rgba(28,28,30,0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .glass-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1rem;
        }
        .glass-light {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Sidebar nav links */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.875rem;
            border-radius: 0.875rem;
            color: rgba(255,255,255,0.5);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }
        .nav-link:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.07);
        }
        .nav-link.active {
            color: #009dde;
            background: rgba(0,157,222,0.12);
        }
        .nav-link.active svg { color: #009dde; }
        .nav-link .badge {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: #009dde;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 1.1rem;
            height: 1.1rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.25rem;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

        /* Primary buttons */
        .btn-primary {
            background: #009dde;
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover { background: #0088c0; transform: scale(1.02); }
        .btn-primary:active { transform: scale(0.98); }

        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.75rem;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.1); color: #fff; }

        /* Status badges */
        .badge-open     { background: rgba(0,157,222,0.15); color: #009dde; }
        .badge-progress { background: rgba(255,204,0,0.15);  color: #ffcc00; }
        .badge-review   { background: rgba(88,86,214,0.15);  color: #5856d6; }
        .badge-done     { background: rgba(52,199,89,0.15);  color: #34c759; }
        .badge-blocked  { background: rgba(255,59,48,0.15);  color: #ff3b30; }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* Input */
        .input-field {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
            color: #fff;
            font-size: 0.875rem;
            width: 100%;
            outline: none;
            transition: border-color 0.2s;
        }
        .input-field:focus { border-color: #009dde; }
        .input-field::placeholder { color: rgba(255,255,255,0.3); }

        /* Timer glow */
        @keyframes pulse-ring {
            0% { opacity: 0.6; transform: scale(1); }
            100% { opacity: 0; transform: scale(1.5); }
        }
        .timer-ring::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 9999px;
            background: rgba(0,157,222,0.3);
            animation: pulse-ring 1.5s ease-out infinite;
        }

        /* Page transition */
        .page-content { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* Smooth theme transition */
        *, *::before, *::after {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        /* Disable transition on images / animations to prevent flicker */
        img, svg, [class*="animate-"] { transition: none !important; }

        /* ===== LIGHT MODE ===== */
        html.light body {
            background: #f0f2f5;
            color: #1c1c1e;
        }
        html.light .glass {
            background: rgba(245,246,250,0.92);
            border-color: rgba(0,0,0,0.08);
        }
        html.light .glass-card {
            background: rgba(255,255,255,0.92);
            border-color: rgba(0,0,0,0.08);
        }
        html.light .glass-light {
            background: rgba(0,0,0,0.04);
            border-color: rgba(0,0,0,0.08);
        }
        html.light .nav-link {
            color: rgba(0,0,0,0.55);
        }
        html.light .nav-link:hover {
            color: rgba(0,0,0,0.85);
            background: rgba(0,0,0,0.06);
        }
        html.light .nav-link.active {
            color: #009dde;
            background: rgba(0,157,222,0.1);
        }
        html.light ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.18);
        }
        html.light .btn-ghost {
            background: rgba(0,0,0,0.05);
            color: rgba(0,0,0,0.7);
            border-color: rgba(0,0,0,0.12);
        }
        html.light .btn-ghost:hover {
            background: rgba(0,0,0,0.09);
            color: #1c1c1e;
        }
        html.light .input-field {
            background: rgba(0,0,0,0.04);
            border-color: rgba(0,0,0,0.12);
            color: #1c1c1e;
        }
        html.light .input-field::placeholder { color: rgba(0,0,0,0.35); }
        /* topbar + sidebar borders in light */
        html.light header, html.light aside {
            border-color: rgba(0,0,0,0.09) !important;
        }
        html.light header {
            background: rgba(245,246,250,0.92) !important;
        }
        html.light header .text-white { color: #1c1c1e !important; }
        html.light header .text-white\/50 { color: rgba(0,0,0,0.45) !important; }
        html.light header .text-white\/40 { color: rgba(0,0,0,0.4) !important; }
        html.light header .hover\:bg-white\/8:hover { background: rgba(0,0,0,0.06) !important; }
        /* Sidebar text colors in light */
        html.light aside .text-white { color: #1c1c1e !important; }
        html.light aside .text-white\/40 { color: rgba(0,0,0,0.45) !important; }
        html.light aside .text-white\/25 { color: rgba(0,0,0,0.35) !important; }
        html.light aside .text-white\/50 { color: rgba(0,0,0,0.5) !important; }
        html.light aside .hover\:bg-white\/6:hover { background: rgba(0,0,0,0.05) !important; }
        html.light aside .border-white\/8 { border-color: rgba(0,0,0,0.08) !important; }
        html.light aside .hover\:text-red-400:hover { color: #dc2626 !important; }
        html.light aside .hover\:bg-red-500\/10:hover { background: rgba(220,38,38,0.08) !important; }
        /* General page light overrides */
        html.light .text-white { color: #1c1c1e !important; }
        html.light .text-white\/90 { color: rgba(0,0,0,0.85) !important; }
        html.light .text-white\/70 { color: rgba(0,0,0,0.65) !important; }
        html.light .text-white\/60 { color: rgba(0,0,0,0.55) !important; }
        html.light .text-white\/50 { color: rgba(0,0,0,0.5) !important; }
        html.light .text-white\/40 { color: rgba(0,0,0,0.4) !important; }
        html.light .text-white\/30 { color: rgba(0,0,0,0.3) !important; }
        html.light .text-white\/25 { color: rgba(0,0,0,0.25) !important; }
        html.light .text-white\/20 { color: rgba(0,0,0,0.2) !important; }
        html.light .bg-black { background: #f0f2f5 !important; }
        html.light .bg-white\/8 { background: rgba(0,0,0,0.05) !important; }
        html.light .bg-white\/6 { background: rgba(0,0,0,0.04) !important; }
        html.light .bg-white\/5 { background: rgba(0,0,0,0.04) !important; }
        html.light .bg-white\/4 { background: rgba(0,0,0,0.03) !important; }
        html.light .border-white\/8 { border-color: rgba(0,0,0,0.08) !important; }
        html.light .border-white\/10 { border-color: rgba(0,0,0,0.1) !important; }
        html.light .border-white\/5 { border-color: rgba(0,0,0,0.05) !important; }
        html.light .hover\:bg-white\/8:hover { background: rgba(0,0,0,0.06) !important; }
        html.light .hover\:bg-white\/6:hover { background: rgba(0,0,0,0.05) !important; }
        html.light .hover\:bg-white\/4:hover { background: rgba(0,0,0,0.04) !important; }
        html.light .placeholder-white\/30::placeholder { color: rgba(0,0,0,0.3) !important; }
    </style>
</head>
<body class="h-full text-white" style="background:#000;" x-data="appLayout()" x-init="init()">

<div class="flex h-full">
    <!-- Sidebar -->
    <aside x-show="sidebarOpen"
           style="display: flex;"
           class="w-64 flex-shrink-0 flex flex-col h-screen sticky top-0 z-20 glass border-r border-white/8">

        <!-- Logo -->
        <div class="px-5 py-5 border-b border-white/8 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #009dde, #0070a8);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="font-heading font-bold text-white text-lg tracking-tight">AgencyOS</span>
            </div>
        </div>

        <!-- Active Timer Banner -->
        <div x-show="activeTimer" x-cloak
             class="mx-3 mt-3 px-3 py-2.5 rounded-xl flex items-center gap-2 cursor-pointer"
             style="background: rgba(0,157,222,0.1); border: 1px solid rgba(0,157,222,0.25);"
             @click="window.location.href='/time'">
            <div class="relative flex-shrink-0">
                <div class="w-2 h-2 rounded-full bg-primary-500 timer-ring"></div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold text-primary-400">Zeit läuft</div>
                <div class="font-mono text-xs text-primary-300" x-text="formatTimer(timerSeconds)">00:00:00</div>
            </div>
            <button @click.stop="stopTimer()" class="text-primary-400 hover:text-red-400 transition-colors p-0.5 rounded">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="6" y="6" width="12" height="12" rx="2" stroke-width="2"/>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">
            <?php
            $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            $isChef = in_array($currentUser['role'] ?? '', ['CHEF', 'admin', 'owner']);

            $navItems = [
                ['href' => '/',             'label' => 'Dashboard',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                ['href' => '/tasks',        'label' => 'Tasks',          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
                ['href' => '/time',         'label' => 'Zeiten',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ['href' => '/projects',     'label' => 'Projekte',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>'],
                ['href' => '/customers',    'label' => 'Kunden',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                ['href' => '/team',         'label' => 'Team Hub',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                ['href' => '/calendar',     'label' => 'Kalender',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                ['href' => '/forms',        'label' => 'Formulare',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                ['href' => '/achievements', 'label' => 'Challenges',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>'],
                ['href' => '/benefits',     'label' => 'Vorteilspass',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>'],
                ['href' => '/chat',         'label' => 'Chat',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>', 'badge' => 'unreadChat'],
                ['href' => '/vacation',     'label' => 'Urlaub',         'icon' => '<circle cx="12" cy="12" r="4" stroke-width="1.75" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>'],
                ['href' => '/access',       'label' => 'Zugänge',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>'],
                ['href' => '/notes',        'label' => 'Notizen',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'],
            ];
            foreach ($navItems as $item):
                $active = $currentPath === $item['href'] || ($item['href'] !== '/' && str_starts_with($currentPath, $item['href']));
                $hasBadge = isset($item['badge']);
            ?>
            <a href="<?= $item['href'] ?>" class="nav-link <?= $active ? 'active' : '' ?>">
                <svg class="w-4.5 h-4.5 flex-shrink-0 w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $item['icon'] ?>
                </svg>
                <span class="flex-1"><?= $item['label'] ?></span>
                <?php if ($hasBadge): ?>
                <span class="badge" x-show="<?= $item['badge'] ?> > 0" x-text="<?= $item['badge'] ?>"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>

            <!-- MEINE LINKS -->
            <div class="pt-3 mt-2 border-t border-white/8">
                <div class="flex items-center justify-between px-2 mb-1">
                    <span class="text-xs font-semibold text-white/25 uppercase tracking-wider">Meine Links</span>
                    <div class="flex items-center gap-1">
                        <button @click="manageCustomLinks()" class="p-1 rounded text-white/25 hover:text-white transition-colors" title="Links verwalten">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <button @click="showCustomLinkModal = true" class="p-1 rounded text-white/25 hover:text-white transition-colors" title="Link hinzufügen">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                </div>
                <template x-for="link in customLinks" :key="link.url">
                    <a :href="link.url" target="_blank" rel="noopener" class="nav-link group">
                        <span x-text="link.emoji || '🔗'" class="text-sm leading-none w-[18px] text-center flex-shrink-0"></span>
                        <span x-text="link.label" class="flex-1 truncate"></span>
                        <svg class="w-3 h-3 text-white/20 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </template>
                <template x-if="customLinks.length === 0">
                    <button @click="showCustomLinkModal = true" class="nav-link w-full text-left text-white/25 hover:text-white/50">
                        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg>
                        <span class="text-xs">Link hinzufügen</span>
                    </button>
                </template>
            </div>

            <?php if ($isChef): ?>
            <!-- ADMIN -->
            <div class="pt-3 mt-2 border-t border-white/8">
                <div class="px-2 mb-1">
                    <span class="text-xs font-semibold text-white/25 uppercase tracking-wider">Admin</span>
                </div>
                <a href="/admin" class="nav-link <?= $currentPath === '/admin' ? 'active' : '' ?>">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Audit-Log
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <!-- User -->
        <div class="px-3 py-4 border-t border-white/8 flex-shrink-0">
            <a href="/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/6 transition-colors">
                <?php if (!empty($currentUser['profile_image'])): ?>
                <img src="<?= htmlspecialchars($currentUser['profile_image']) ?>"
                     class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                <?php else: ?>
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                     style="background: linear-gradient(135deg, #009dde, #0070a8);">
                    <?= strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)) . strtoupper(substr($currentUser['last_name'] ?? '', 0, 1)) ?>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">
                        <?= htmlspecialchars(trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''))) ?>
                    </div>
                    <div class="text-xs text-white/40 truncate"><?= htmlspecialchars($currentUser['role'] ?? '') ?></div>
                </div>
            </a>
            <a href="/logout" class="flex items-center gap-3 px-3 py-2 mt-0.5 rounded-xl text-white/40 hover:text-red-400 hover:bg-red-500/10 transition-colors text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Abmelden
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-14 border-b border-white/8 flex items-center px-6 gap-4 flex-shrink-0"
                style="background: rgba(0,0,0,0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
            <button @click="toggleSidebar()"
                    class="text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="font-heading text-lg font-semibold text-white flex-1"><?= htmlspecialchars($pageTitle ?? '') ?></h1>

            <!-- Handbook -->
            <a href="/handbuch" class="text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8" title="Handbuch">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </a>

            <!-- Notification bell -->
            <div class="relative" x-data="{ notifOpen: false }">
                <button @click="notifOpen = !notifOpen"
                        class="relative text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span x-show="unreadNotifications > 0" x-cloak
                          class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-red-500"></span>
                </button>
                <!-- Notification dropdown -->
                <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                     x-transition class="absolute right-0 top-10 w-80 glass-card rounded-2xl shadow-2xl z-50 overflow-hidden" style="border: 1px solid rgba(255,255,255,0.1);">
                    <div class="px-4 py-3 border-b border-white/8 flex items-center justify-between">
                        <span class="text-sm font-semibold text-white">Benachrichtigungen</span>
                        <button @click="markAllRead()" class="text-xs text-white/40 hover:text-white transition-colors">Alle gelesen</button>
                    </div>
                    <div class="max-h-72 overflow-y-auto">
                        <template x-if="notifications.length === 0">
                            <p class="text-xs text-white/30 text-center py-6">Keine Benachrichtigungen</p>
                        </template>
                        <template x-for="n in notifications" :key="n.id">
                            <div class="px-4 py-3 border-b border-white/5 hover:bg-white/4 transition-colors cursor-pointer"
                                 :class="n.is_read ? 'opacity-50' : ''"
                                 @click="readNotif(n)">
                                <p class="text-xs font-medium text-white" x-text="n.title"></p>
                                <p class="text-xs text-white/50 mt-0.5 truncate" x-text="n.message"></p>
                                <p class="text-xs text-white/25 mt-1" x-text="formatNotifTime(n.created_at)"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Settings (Admin only) -->
            <?php if ($isChef): ?>
            <a href="/admin" class="text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8" title="Einstellungen">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
            <?php endif; ?>

            <!-- Theme toggle: dark / light / system -->
            <button @click="cycleTheme()"
                    class="text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8"
                    :title="themeLabel()">
                <!-- moon = dark -->
                <template x-if="themeMode === 'dark'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </template>
                <!-- sun = light -->
                <template x-if="themeMode === 'light'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                    </svg>
                </template>
                <!-- monitor = system -->
                <template x-if="themeMode === 'system'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H4a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-1"/>
                    </svg>
                </template>
            </button>

            <!-- Profile -->
            <a href="/profile" class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-white/6 transition-colors">
                <?php if (!empty($currentUser['profile_image'])): ?>
                <img src="<?= htmlspecialchars($currentUser['profile_image']) ?>"
                     class="w-7 h-7 rounded-full object-cover" alt="">
                <?php else: ?>
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold"
                     style="background: linear-gradient(135deg, #009dde, #0070a8);">
                    <?= strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)) ?>
                </div>
                <?php endif; ?>
                <span class="text-sm font-medium text-white hidden md:block"><?= htmlspecialchars($currentUser['first_name'] ?? '') ?></span>
                <svg class="w-4 h-4 text-white/40 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </a>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6 page-content">

<!-- Custom Links Modal (managed at body/appLayout level) -->
<div x-show="showCustomLinkModal" x-cloak
     class="fixed inset-0 z-[100] flex items-center justify-center"
     style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
     @click.self="showCustomLinkModal = false">
    <div class="glass-card rounded-2xl p-6 w-full max-w-sm mx-4 shadow-2xl">
        <h3 class="text-base font-semibold text-white font-heading mb-4">Meine Links</h3>

        <!-- Existing links list -->
        <div class="space-y-2 mb-4 max-h-48 overflow-y-auto" x-show="customLinks.length > 0">
            <template x-for="link in customLinks" :key="link.url">
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5">
                    <span x-text="link.emoji || '🔗'" class="text-sm flex-shrink-0"></span>
                    <span x-text="link.label" class="flex-1 text-sm text-white/80 truncate"></span>
                    <button @click="deleteCustomLink(link)"
                            class="text-white/30 hover:text-red-400 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Add new link -->
        <div class="space-y-3">
            <p class="text-xs text-white/40 font-medium uppercase tracking-wider">Neuen Link hinzufügen</p>
            <div class="flex gap-2">
                <input x-model="newLink.emoji" type="text" placeholder="🔗"
                       class="input-field w-14 text-center text-lg px-2">
                <input x-model="newLink.label" type="text" placeholder="Bezeichnung"
                       class="input-field flex-1">
            </div>
            <input x-model="newLink.url" type="text" placeholder="https://..."
                   class="input-field" @keydown.enter="saveCustomLink()">
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button @click="showCustomLinkModal = false" class="btn-ghost text-sm px-4 py-2">Schließen</button>
            <button @click="saveCustomLink()"
                    :disabled="!newLink.label || !newLink.url"
                    class="btn-primary text-sm px-4 py-2">Hinzufügen</button>
        </div>
    </div>
</div>
