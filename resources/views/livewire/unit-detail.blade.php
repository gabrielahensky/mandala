<div class="px-6 py-6 space-y-8">

    {{-- HEADER --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold">
                {{ $unit->name }}
            </h1>

            @if ($activeRent)
                <p class="text-sm text-green-600 mt-1">
                    Occupied by
                    <a href="/tenants/{{ $activeRent->tenant->id }}"
                       class="underline font-medium">
                        {{ $activeRent->tenant->name }}
                    </a>
                </p>
            @else
                <p class="text-sm text-gray-500 mt-1">
                    Vacant
                </p>
            @endif
        </div>

        {{-- ACTION --}}
        <div>
            @if ($activeRent)
                <button
                    wire:click="openEndRent"
                    class="px-4 py-2 bg-red-600 text-white text-sm rounded">
                    End Rent
                </button>
            @else
                <button
                    wire:click="openStartRent"
                    class="px-4 py-2 bg-green-600 text-white text-sm rounded">
                    Start Rent
                </button>
            @endif
        </div>
    </div>

    {{-- BILLING SUMMARY --}}
    @if ($activeRent && $billingSummary)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

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
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Note</th>
                    <th class="px-4 py-2 text-right">Amount</th>
                    <th class="px-4 py-2 text-left">Flags</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rentTransactions as $tx)
                    <tr class="border-t hover:bg-gray-50">
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
                        <td colspan="4"
                            class="px-4 py-6 text-center text-gray-500">
                            No rent transactions
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- START RENT MODAL --}}
    @if ($showStartRentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl p-5 space-y-4">

                <h2 class="text-lg font-semibold">
                    Start Rent — {{ $unit->name }}
                </h2>

                <div class="space-y-3">

                    <div>
                        <label class="text-sm text-gray-600">Tenant</label>
                        <select wire:model="tenantId"
                                class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">Select tenant</option>
                            @foreach ($this->tenants as $tenant)
                                <option value="{{ $tenant->id }}">
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tenantId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Monthly Rent</label>
                        <input type="number"
                               wire:model="monthlyRent"
                               class="w-full border rounded px-3 py-2 text-sm">
                        @error('monthlyRent') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Note</label>
                        <textarea wire:model="note"
                                  class="w-full border rounded px-3 py-2 text-sm"
                                  rows="2"></textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button wire:click="closeStartRent"
                            class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button wire:click="confirmStartRent"
                            class="px-4 py-2 bg-green-600 text-white text-sm rounded">
                        Start Rent
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- END RENT MODAL --}}
    @if ($showEndRentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl p-5 space-y-4">

                <h2 class="text-lg font-semibold text-red-600">
                    End Rent — {{ $unit->name }}
                </h2>

                <p class="text-sm text-gray-600">
                    This will end the active rent.  
                    Ledger history will remain unchanged.
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button wire:click="closeEndRent"
                            class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button wire:click="confirmEndRent"
                            class="px-4 py-2 bg-red-600 text-white text-sm rounded">
                        End Rent
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
