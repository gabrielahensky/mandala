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

        <!-- BRAND -->
        <div class="px-6 py-4 border-b border-gray-800 space-y-1">
            <div class="flex items-center gap-2">
                <x-app-logo />
                
            </div>

            <p class="text-xs text-gray-400">
                Internal Dashboard
            </p>
        </div>

        <!-- MENU -->
        <nav class="flex-1 px-3 py-4 space-y-6 text-sm">

            <!-- FINANCE -->
            <div>
                <p class="px-3 mb-2 text-xs uppercase tracking-wide text-gray-500">
                    Finance
                </p>

                <a href="/"
                class="block px-3 py-2 rounded
                {{ request()->is('/') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800/50' }}">
                    Dashboard
                </a>

                <a href="/ledger"
                class="block px-3 py-2 rounded
                {{ request()->is('ledger*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800/50' }}">
                    Ledger
                </a>

                <a href="/rent/unpaid"
                class="flex items-center justify-between px-3 py-2 rounded
                {{ request()->is('rent/unpaid')
                        ? 'bg-gray-800 text-white'
                        : ($unpaidCount > 0
                            ? 'text-red-300 hover:text-white hover:bg-red-900/30'
                            : 'text-gray-400 hover:text-white hover:bg-gray-800/50') }}">

                    <span>Unpaid Rent</span>

                    @if ($unpaidCount > 0)
                        <span class="ml-2 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-semibold bg-red-600 text-white rounded-full">
                            {{ $unpaidCount }}
                        </span>
                    @endif
                </a>
            </div>

            <!-- KOS MANAGEMENT -->
            <div>
                <p class="px-3 mb-2 text-xs uppercase tracking-wide text-gray-500">
                    Kos Management
                </p>

                <a href="/units"
                   class="block px-3 py-2 rounded
                   {{ request()->is('units*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white' }}">
                    Units
                </a>

                <a href="/tenants"
                   class="block px-3 py-2 rounded
                   {{ request()->is('tenants*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white' }}">
                    Tenants
                </a>
            </div>

            <!-- SYSTEM -->
            <div>
                <p class="px-3 mb-2 text-xs uppercase tracking-wide text-gray-500">
                    System
                </p>

                <span class="block px-3 py-2 rounded text-gray-500 cursor-not-allowed">
                    Settings
                </span>
            </div>

        </nav>

        <!-- FOOTER -->
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
