<!DOCTYPE html>
<html lang="de" class="h-full bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgencyOS – Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca' }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center bg-gray-950">
<div class="w-full max-w-md px-6">

    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-500 mb-4">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">AgencyOS</h1>
        <p class="text-gray-400 mt-1">Bitte melde dich an</p>
    </div>

    <!-- Error -->
    <?php if (!empty($loginError)): ?>
    <div class="mb-4 bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded-xl text-sm">
        <?= htmlspecialchars($loginError) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="/login" class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">E-Mail oder Benutzername</label>
            <input type="text" name="login"
                   value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                   autofocus
                   class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent placeholder-gray-500"
                   placeholder="dein@email.de">
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Passwort</label>
            <input type="password" name="password"
                   class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent placeholder-gray-500"
                   placeholder="••••••••">
        </div>
        <button type="submit"
                class="w-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-3 px-4 rounded-xl transition-colors duration-150">
            Anmelden
        </button>
    </form>

    <p class="text-center text-xs text-gray-600 mt-6">&copy; <?= date('Y') ?> AgencyOS</p>
</div>
</body>
</html>
