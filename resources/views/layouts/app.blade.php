<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mandala</title>

    {{-- VITE ASSETS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- LIVEWIRE --}}
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans">
    {{ $slot }}

    @livewireScripts
</body>
</html>
