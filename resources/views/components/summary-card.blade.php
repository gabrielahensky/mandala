@props([
    'label',
    'value' => 0,
    'color' => 'text-gray-900'
])

<div class="bg-white p-5 rounded-xl shadow-sm w-56">
    <p class="text-xs uppercase tracking-wide text-gray-400">
        {{ $label }}
    </p>

    <p class="mt-1 text-2xl font-semibold {{ $color }}">
        Rp {{ number_format($value) }}
    </p>
</div>
