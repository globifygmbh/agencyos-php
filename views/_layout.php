<!DOCTYPE html>
<html lang="de" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'AgencyOS') ?> – AgencyOS</title>
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

                init() {
                    this.loadTimerState();
                    this.loadUnreadCounts();
                    this.loadCustomLinks();
                    setInterval(() => this.loadUnreadCounts(), 30000);
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
    </style>
</head>
<body class="h-full bg-black text-white" x-data="appLayout()" x-init="init()">

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
                ['href' => '/tasks',        'label' => 'Aufgaben',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
                ['href' => '/projects',     'label' => 'Projekte',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>'],
                ['href' => '/customers',    'label' => 'Kunden',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
                ['href' => '/team',         'label' => 'Team',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                ['href' => '/time',         'label' => 'Zeiterfassung',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ['href' => '/calendar',     'label' => 'Kalender',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                ['href' => '/chat',         'label' => 'Chat',           'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>', 'badge' => 'unreadChat'],
                ['href' => '/vacation',     'label' => 'Urlaub',         'icon' => '<circle cx="12" cy="12" r="4" stroke-width="1.75" stroke-linecap="round"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>'],
                ['href' => '/achievements', 'label' => 'Challenges',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>'],
                ['href' => '/benefits',     'label' => 'Benefits',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>'],
                ['href' => '/notes',        'label' => 'Notizen',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'],
                ['href' => '/access',       'label' => 'Zugänge',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>'],
                ['href' => '/forms',        'label' => 'Formulare',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                ['href' => '/ai',           'label' => 'KI-Assistent',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H4a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-1"/>'],
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

            <?php if ($isChef): ?>
            <div class="pt-2 mt-2 border-t border-white/8">
                <a href="/admin" class="nav-link <?= $currentPath === '/admin' ? 'active' : '' ?>">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Admin
                </a>
            </div>
            <?php endif; ?>

            <!-- Custom Links -->
            <div x-show="customLinks.length > 0" class="pt-2 mt-2 border-t border-white/8">
                <template x-for="link in customLinks" :key="link.url">
                    <a :href="link.url" target="_blank" class="nav-link">
                        <span x-text="link.emoji" class="text-base leading-none w-[18px] text-center flex-shrink-0"></span>
                        <span x-text="link.label" class="flex-1 truncate"></span>
                    </a>
                </template>
            </div>

            <!-- Add Custom Link -->
            <button @click="showCustomLinkModal = true"
                    class="nav-link w-full text-left mt-1">
                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="text-xs">Link hinzufügen</span>
            </button>
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

            <!-- Notification bell -->
            <a href="/admin" class="relative text-white/50 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/8">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span x-show="unreadNotifications > 0" x-cloak
                      class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-primary-500"></span>
            </a>

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
            </a>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6 page-content">
