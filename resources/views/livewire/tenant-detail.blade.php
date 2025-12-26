<div class="px-6 py-6 space-y-8">

    {{-- =========================
        TOP BAR
    ========================== --}}
    <div>
        <a href="/tenants"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
            ← Back to Tenants
        </a>
    </div>

    {{-- =========================
        TENANT HEADER
    ========================== --}}
    <div class="bg-white border rounded-xl p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ $tenant->name }}
                </h1>

                {{-- STATUS — STRICT DOMAIN --}}
                @if ($activeRent)
                    <span class="mt-1 inline-flex items-center gap-2 text-sm
                                 bg-green-100 text-green-700 px-3 py-1 rounded-full">
                        ● Active
                        <span class="font-medium">
                            — {{ $activeRent->unit->name }}
                        </span>
                    </span>
                @else
                    <span class="mt-1 inline-flex items-center text-sm
                                 bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                        Inactive
                    </span>
                @endif
            </div>

            {{-- ACTIONS --}}
            <div class="flex flex-wrap gap-2">
                @if (! $activeRent)
                    <button
                        type="button"
                        wire:click="openAssignUnitModal"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg">
                        Assign Unit
                    </button>
                @endif

                @if ($activeRent)
                    <button
                        type="button"
                        wire:click="openPaymentModal"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded-lg">
                        Add Payment
                    </button>

                    <button
                        type="button"
                        wire:click="openEndRentModal"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">
                        End Rent
                    </button>
                @endif
            </div>

        </div>
    </div>

    {{-- =========================
        BILLING SUMMARY (ONLY IF ACTIVE)
    ========================== --}}
    @if ($activeRent && $tenantBillingSummary)
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border">
                <p class="text-xs text-gray-500">Total Paid</p>
                <p class="font-semibold">
                    Rp {{ number_format($tenantBillingSummary['paid_total']) }}
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <p class="text-xs text-gray-500">Total Rent Due</p>
                <p class="font-semibold">
                    Rp {{ number_format($tenantBillingSummary['expected_total']) }}
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <p class="text-xs text-gray-500">Outstanding</p>
                @if ($tenantBillingSummary['debt'] > 0)
                    <p class="font-semibold text-red-600">
                        Rp {{ number_format($tenantBillingSummary['debt']) }}
                    </p>
                @else
                    <p class="font-semibold text-green-600">
                        Settled
                    </p>
                @endif
            </div>
        </section>
    @endif

    {{-- =========================
        RENT INVOICES (ACTIVE ONLY)
    ========================== --}}
    @if ($activeRent)
        <section class="bg-white rounded-xl border p-5 space-y-4">
            <h2 class="text-lg font-semibold">Outstanding Invoices</h2>

            @forelse ($activeInvoices as $invoice)
                <div class="flex justify-between border rounded-lg p-3">
                    <div>
                        <p class="font-medium">
                            {{ $invoice->billing_month->format('F Y') }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Due {{ $invoice->due_date->format('d M Y') }}
                        </p>
                    </div>
                    <div class="font-semibold">
                        Rp {{ number_format($invoice->amount) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No unpaid invoices 🎉
                </p>
            @endforelse
        </section>
    @endif

    {{-- =========================
        RENT TRANSACTIONS (ACTIVE ONLY)
    ========================== --}}
    @if ($activeRent)
        <section class="bg-white rounded-xl border overflow-x-auto">
            <div class="px-5 py-4 border-b">
                <h2 class="text-lg font-semibold">Rent Transactions</h2>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Note</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rentTransactions as $tx)
                        <tr class="border-t">
                            <td class="px-4 py-2 text-xs text-gray-500">
                                {{ $tx->transacted_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $tx->note ?: '—' }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                Rp {{ number_format($tx->amount) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                class="px-4 py-6 text-center text-gray-500">
                                No transactions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif

    {{-- =========================
        ASSIGN UNIT MODAL
    ========================== --}}
    @if ($showAssignUnitModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 space-y-6">

                <div>
                    <h2 class="text-lg font-semibold">Assign Unit</h2>
                    <p class="text-sm text-gray-500">
                        Assign a unit to this tenant
                    </p>
                </div>

                {{-- UNIT --}}
                <div>
                    <label class="text-xs text-gray-500">Available Unit</label>
                    <select
                        wire:model="selectedUnitId"
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">— Select unit —</option>

                        @foreach ($this->availableUnits as $unit)
                            <option value="{{ $unit->id }}">
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('selectedUnitId')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- START DATE --}}
                <div>
                    <label class="text-xs text-gray-500">Start Date</label>
                    <input
                        type="date"
                        wire:model="startDate"
                        class="w-full border rounded-lg px-3 py-2 text-sm">
                    @error('startDate')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ACTION --}}
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        wire:click="closeAssignUnitModal"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="assignUnit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg">
                        Assign
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- =========================
        END RENT MODAL
    ========================== --}}
    @if ($showEndRentModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-semibold text-red-600">
                    End Rent — {{ $tenant->name }}
                </h2>

                <div>
                    <label class="text-xs text-gray-500">End Date</label>
                    <input type="date" wire:model="endDate"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs text-gray-500">Note</label>
                    <textarea wire:model="endNote" rows="2"
                              class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button"
                            wire:click="closeEndRentModal"
                            class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="endRent"
                            class="px-4 py-2 bg-red-600 text-white text-sm rounded">
                        Confirm End Rent
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================
        PAYMENT MODAL
    ========================== --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    Add Rent Payment — {{ $tenant->name }}
                </h2>

                <div>
                    <label class="text-xs text-gray-500">Amount</label>
                    <input type="number" wire:model.defer="amount"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs text-gray-500">Payment Date</label>
                    <input type="date" wire:model.defer="paidAt"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="text-xs text-gray-500">Note</label>
                    <input type="text" wire:model.defer="note"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button"
                            wire:click="closePaymentModal"
                            class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="savePayment"
                            class="px-4 py-2 bg-green-600 text-white text-sm rounded">
                        Save Payment
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
