<div class="px-6 py-6 space-y-6">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
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
                class="border rounded-lg px-3 py-2 text-sm"
            />

            <span
                wire:loading
                wire:target="month"
                class="text-xs text-gray-400">
                Updating…
            </span>
        </div>
    </div>

    {{-- =====================================================
        TABLE
    ====================================================== --}}
    <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left">Unit</th>
                    <th class="px-4 py-2 text-left">Tenant</th>
                    <th class="px-4 py-2 text-right">Expected</th>
                    <th class="px-4 py-2 text-right">Paid</th>
                    <th class="px-4 py-2 text-right text-red-600">Outstanding</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $index => $row)
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

                        <td class="px-4 py-2 text-right">
                            <button
                                type="button"
                                wire:click="openPaymentModal({{ $index }})"
                                class="text-xs text-blue-600 hover:underline font-medium">
                                Pay
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"
                            class="px-4 py-10 text-center text-gray-500">
                            All rents paid 🎉
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- =====================================================
        PAYMENT MODAL
    ====================================================== --}}
    @if ($showPaymentModal && $rentContext)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 space-y-6">

                {{-- HEADER --}}
                <div>
                    <h2 class="text-lg font-semibold">
                        Pay Rent
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ $rentContext->tenant->name }}
                        —
                        {{ $rentContext->unit->name }}
                    </p>
                </div>

                {{-- SUMMARY --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Unit</p>
                        <p class="font-medium">
                            {{ $rentContext->unit->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Tenant</p>
                        <p class="font-medium">
                            {{ $rentContext->tenant->name }}
                        </p>
                    </div>
                </div>

                <div class="border-t"></div>

                {{-- PAYMENT FORM --}}
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-gray-500">Amount</label>
                        <input
                            type="number"
                            wire:model.defer="amount"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-500">Payment Date</label>
                        <input
                            type="date"
                            wire:model.defer="paidAt"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('paidAt')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-500">Note</label>
                        <input
                            type="text"
                            wire:model.defer="note"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('note')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="savePayment"
                        wire:loading.attr="disabled"
                        wire:target="savePayment"
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg disabled:opacity-50">
                        Save Payment
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
