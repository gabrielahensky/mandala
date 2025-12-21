<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mandala</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gray-900 text-gray-100 flex flex-col">
        <!-- Logo / Brand -->
        <div class="px-6 py-4 border-b border-gray-800 space-y-1">
            <x-app-logo />

            <p class="text-xs text-gray-400">
                Internal Dashboard
            </p>
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">

            <a href="/"
            class="block px-3 py-2 rounded
                    {{ request()->is('/') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white' }}">
                Dashboard
            </a>

            <a href="/ledger"
            class="block px-3 py-2 rounded
                    {{ request()->is('ledger') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white' }}">
                Ledger
            </a>

            <span class="block px-3 py-2 rounded text-gray-500 cursor-not-allowed">
                Settings
            </span>

        </nav>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-800 text-xs text-gray-400">
            v0.1 • Mandala
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto">
        {{ $slot }}
    </main>

</div>

@livewireScripts
</body>
</html>
