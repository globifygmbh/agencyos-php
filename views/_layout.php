<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'AgencyOS') ?> – AgencyOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' }
                    }
                }
            }
        }

        // currentUser als globale JS-Variable – kein JSON in x-data Attribut
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
                toggleSidebar() { this.sidebarOpen = !this.sidebarOpen; }
            };
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.625rem 0.75rem; border-radius: 0.75rem;
            color: #9ca3af; font-size: 0.875rem; font-weight: 500;
            text-decoration: none; transition: all 0.15s;
        }
        .sidebar-link:hover { color: #fff; background: #1f2937; }
        .sidebar-link.active { background: rgba(99,102,241,0.1); color: #818cf8; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
    </style>
</head>
<body class="h-full bg-gray-950 text-white" x-data="appLayout()">

<div class="flex h-full">
    <!-- Sidebar -->
    <aside x-show="sidebarOpen"
           style="display: flex;"
           class="w-60 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col h-screen sticky top-0 z-20">

        <!-- Logo -->
        <div class="px-5 py-5 border-b border-gray-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="font-bold text-white text-lg">AgencyOS</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
            <?php
            $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            $navItems = [
                ['href' => '/',             'label' => 'Dashboard',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['href' => '/tasks',        'label' => 'Aufgaben',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['href' => '/projects',     'label' => 'Projekte',      'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['href' => '/customers',    'label' => 'Kunden',        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['href' => '/time',         'label' => 'Zeiterfassung', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['href' => '/calendar',     'label' => 'Kalender',      'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['href' => '/chat',         'label' => 'Chat',          'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ['href' => '/vacation',     'label' => 'Urlaub',        'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['href' => '/achievements', 'label' => 'Achievements',  'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                ['href' => '/benefits',     'label' => 'Benefits',      'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
                ['href' => '/forms',        'label' => 'Formulare',     'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['href' => '/ai',           'label' => 'KI-Assistent',  'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H4a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-1'],
            ];
            foreach ($navItems as $item):
                $active = $currentPath === $item['href'] || ($item['href'] !== '/' && str_starts_with($currentPath, $item['href']));
            ?>
            <a href="<?= $item['href'] ?>" class="sidebar-link <?= $active ? 'active' : '' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="<?= $item['icon'] ?>"/>
                </svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>

            <?php if (in_array($currentUser['role'] ?? '', ['CHEF', 'admin', 'owner'])): ?>
            <div class="pt-3 mt-3 border-t border-gray-800">
                <a href="/admin" class="sidebar-link <?= $currentPath === '/admin' ? 'active' : '' ?>">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Admin
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <!-- User -->
        <div class="px-3 py-4 border-t border-gray-800 flex-shrink-0">
            <a href="/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-800 transition-colors">
                <?php if (!empty($currentUser['profile_image'])): ?>
                <img src="<?= htmlspecialchars($currentUser['profile_image']) ?>"
                     class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    <?= strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)) . strtoupper(substr($currentUser['last_name'] ?? '', 0, 1)) ?>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">
                        <?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?>
                    </div>
                    <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($currentUser['role'] ?? '') ?></div>
                </div>
            </a>
            <a href="/logout" class="flex items-center gap-3 px-3 py-2 mt-1 rounded-xl text-gray-500 hover:text-red-400 hover:bg-red-900/20 transition-colors text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Abmelden
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-14 border-b border-gray-800 flex items-center px-6 gap-4 flex-shrink-0 bg-gray-900/50">
            <button @click="toggleSidebar()"
                    class="text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-gray-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-white flex-1"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6">
