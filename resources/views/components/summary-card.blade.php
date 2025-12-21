@props([
    'label',
    'value' => 0,
    'color' => 'text-gray-900'
])

<div class="bg-white p-5 rounded-lg shadow-sm w-52">
    <p class="text-sm text-gray-500">
        {{ $label }}
    </p>

    <p class="text-xl font-bold {{ $color }}">
        Rp {{ number_format($value) }}
    </p>
</div>
