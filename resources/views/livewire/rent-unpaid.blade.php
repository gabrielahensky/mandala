<div class="px-6 py-6 space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Unpaid Rents
            </h1>
            <p class="text-sm text-gray-500">
                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <input
                type="month"
                wire:model="month"
                class="border rounded px-3 py-2 text-sm"
            />

            <span
                wire:loading
                wire:target="month"
                class="text-xs text-gray-400"
            >
                Updating…
            </span>
        </div>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Unit</th>
                    <th class="px-4 py-2 text-left">Tenant</th>
                    <th class="px-4 py-2 text-right">Expected</th>
                    <th class="px-4 py-2 text-right">Paid</th>
                    <th class="px-4 py-2 text-right text-red-600">Outstanding</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $row)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">
                        {{ $row['unit']->name }}
                    </td>

                    <td class="px-4 py-2">
                        {{ $row['tenant']->name }}
                    </td>

                    <td class="px-4 py-2 text-right">
                        Rp {{ number_format($row['expected']) }}
                    </td>

                    <td class="px-4 py-2 text-right text-green-600">
                        Rp {{ number_format($row['paid']) }}
                    </td>

                    <td class="px-4 py-2 text-right text-red-600 font-semibold">
                        Rp {{ number_format($row['outstanding']) }}
                    </td>

                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">
                            UNPAID
                        </span>
                    </td>

                    <td class="px-4 py-2 text-right">
                        <a
                            href="/units/{{ $row['unit']->id }}"
                            class="text-xs text-blue-600 hover:underline"
                        >
                            View Unit
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                        All rents paid 🎉
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
