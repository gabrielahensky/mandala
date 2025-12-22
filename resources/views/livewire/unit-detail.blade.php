<div class="px-6 py-6 space-y-8">

    {{-- HEADER --}}
    <div>
        <h1 class="text-2xl font-bold">
            {{ $unit->name }}
        </h1>

        @if ($activeRent)
            <p class="text-sm text-green-600">
                Occupied by {{ $activeRent->tenant->name }}
            </p>
        @else
            <p class="text-sm text-gray-500">
                Vacant
            </p>
        @endif
    </div>

    {{-- RENT STATUS (CARRY-AWARE) --}}
    @if ($activeRent && $billingSummary)
        <div class="grid grid-cols-3 gap-4">

            <div class="bg-white p-4 rounded-lg border">
                <p class="text-xs text-gray-500">Total Paid</p>
                <p class="font-semibold">
                    Rp {{ number_format($billingSummary['paid_total']) }}
                </p>
            </div>

            <div class="bg-white p-4 rounded-lg border">
                <p class="text-xs text-gray-500">Expected Until Now</p>
                <p class="font-semibold">
                    Rp {{ number_format($billingSummary['expected_total']) }}
                </p>
            </div>

            <div class="bg-white p-4 rounded-lg border">
                <p class="text-xs text-gray-500">Balance</p>

                @if ($billingSummary['credit'] > 0)
                    <p class="text-green-600 font-semibold">
                        Credit: Rp {{ number_format($billingSummary['credit']) }}
                    </p>
                @elseif ($billingSummary['debt'] > 0)
                    <p class="text-red-600 font-semibold">
                        Debt: Rp {{ number_format($billingSummary['debt']) }}
                    </p>
                @else
                    <p class="text-gray-600 font-semibold">
                        Settled
                    </p>
                @endif
            </div>

        </div>
    @endif

    {{-- RENT TRANSACTIONS --}}
    <div class="bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Note</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2">Flags</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rentTransactions as $tx)
                    <tr class="border-t">
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ $tx->transacted_at->format('d M Y') }}
                        </td>

                        <td class="px-4 py-2">
                            {{ $tx->note ?? '-' }}
                        </td>

                        <td class="px-4 py-2 text-right">
                            Rp {{ number_format($tx->amount) }}
                        </td>

                        <td class="px-4 py-2 text-xs space-x-1">
                            @if ($tx->isCorrection())
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded">
                                    Corrected
                                </span>
                            @endif

                            @if ($tx->evidences()->exists())
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">
                                    Evidence
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No rent transactions
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
