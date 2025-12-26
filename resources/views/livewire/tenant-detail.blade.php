<div class="px-6 py-6 space-y-8">

    {{-- BACK --}}
    <div>
        <a href="/tenants"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
            ← Back to Tenants
        </a>
    </div>

    {{-- TENANT HEADER --}}
    <section class="bg-white border rounded-xl p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">

            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ $tenant->name }}
                </h1>

                {{-- STATUS (STRICT DOMAIN) --}}
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
                        wire:click="openAssignUnitModal"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg">
                        Assign Unit
                    </button>
                @endif

                @if ($activeRent)
                    <button
                        wire:click="openPaymentModal"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded-lg">
                        Add Payment
                    </button>

                    <button
                        wire:click="openEndRentModal"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">
                        End Rent
                    </button>
                @endif

            </div>
        </div>
    </section>

    {{-- BILLING SUMMARY --}}
    @if ($activeRent && $tenantBillingSummary)
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Total Paid</p>
                <p class="font-semibold">
                    Rp {{ number_format($tenantBillingSummary['paid_total']) }}
                </p>
            </div>

            <div class="bg-white border rounded-xl p-4">
                <p class="text-xs text-gray-500">Total Rent Due</p>
                <p class="font-semibold">
                    Rp {{ number_format($tenantBillingSummary['expected_total']) }}
                </p>
            </div>

            <div class="bg-white border rounded-xl p-4">
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

    {{-- OUTSTANDING INVOICES --}}
    @if ($activeRent)
        <section class="bg-white border rounded-xl p-5 space-y-4">
            <h2 class="text-lg font-semibold">Outstanding Invoices</h2>

            @forelse ($activeInvoices as $invoice)
                <div class="flex justify-between border rounded-lg p-3">
                    <div>
                        <p class="font-medium">
                            {{ $invoice->billing_month->format('F Y') }}
                        </p>
                        <p class="text-xs text-gray-500">
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

    {{-- RENT TRANSACTIONS --}}
    @if ($activeRent)
        <section class="bg-white border rounded-xl overflow-hidden">
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

</div>
