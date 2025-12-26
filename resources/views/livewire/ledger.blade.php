<div class="px-4 py-6 sm:px-8 sm:py-8 space-y-6">

    {{-- ======================================================
        HEADER
    ======================================================= --}}
    <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Financial Records</h1>

            <p class="text-sm text-gray-500">
                History of money coming in and going out
                @if ($month)
                    • {{ $month }}
                @endif
            </p>

            <div class="pt-3">
                <button
                    wire:click="openTransactionModal"
                    class="px-4 py-2 bg-gray-900 text-white text-sm rounded hover:bg-gray-800">
                    + Record New Transaction
                </button>

                <p class="text-xs text-gray-400 mt-1">
                    Use this for manual income or expenses
                </p>
            </div>
        </div>

        <a href="/dashboard"
           class="text-sm text-gray-500 hover:underline self-start">
            ← Back to dashboard
        </a>
    </div>

    {{-- ======================================================
        FILTER BAR
    ======================================================= --}}
    <div class="bg-white border rounded-xl p-4 flex flex-wrap gap-4 items-end">

        <div>
            <label class="block text-xs text-gray-500 mb-1">
                Month
            </label>
            <input
                type="month"
                wire:model.live="month"
                class="border rounded px-3 py-2 text-sm w-40"
            />
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">
                Filter by unit
            </label>
            <select
                wire:model.live="filterUnitId"
                class="border rounded px-3 py-2 text-sm w-44"
            >
                <option value="">All units</option>
                @foreach ($this->units as $unit)
                    <option value="{{ $unit->id }}">
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">
                Filter by tenant
            </label>
            <select
                wire:model.live="filterTenantId"
                class="border rounded px-3 py-2 text-sm w-44"
            >
                <option value="">All tenants</option>
                @foreach ($this->tenants as $tenant)
                    <option value="{{ $tenant->id }}">
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($month || $filterUnitId || $filterTenantId)
            <div class="pt-5">
                <button
                    wire:click="clearFilters"
                    class="text-xs text-gray-500 hover:underline">
                    Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- ======================================================
        TRANSACTION LIST
    ======================================================= --}}
    <div class="bg-white border rounded-xl divide-y">

        @forelse ($transactions as $t)
            <div class="p-4 flex flex-col sm:flex-row sm:justify-between gap-4">

                {{-- LEFT --}}
                <div class="space-y-1">
                    <p class="font-medium text-sm">
                        {{ $t->category }}

                        @if ($t->isCorrection())
                            <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">
                                Correction
                            </span>
                        @endif
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $t->transacted_at->format('d M Y') }}
                        @if ($t->note)
                            • {{ $t->note }}
                        @endif
                    </p>

                    {{-- CONTEXT TAGS --}}
                    @if ($t->tags->isNotEmpty())
                        <div class="flex gap-2 text-xs text-gray-400">
                            @foreach ($t->tags as $tag)
                                <span class="px-2 py-0.5 border rounded">
                                    {{ strtoupper($tag->type) }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- RIGHT --}}
                <div class="text-right space-y-1">
                    <p class="font-semibold
                        {{ $t->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $t->type === 'income' ? '+' : '–' }}
                        Rp {{ number_format($t->amount) }}
                    </p>

                    @if (! $t->isCorrection())
                        <button
                            wire:click="openCorrection({{ $t->id }})"
                            class="text-xs text-blue-600 hover:underline">
                            Fix this record
                        </button>
                    @endif
                </div>

            </div>
        @empty
            <div class="p-6 text-center text-gray-500 text-sm">
                No financial records found for this filter
            </div>
        @endforelse
    </div>

    {{-- ======================================================
        PAGINATION
    ======================================================= --}}
    <div>
        {{ $transactions->links() }}
    </div>

    {{-- ======================================================
        ADD TRANSACTION MODAL
    ======================================================= --}}
    @if ($showTransactionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-lg rounded-xl p-6 space-y-4">

                <h2 class="text-lg font-semibold">
                    Record a Transaction
                </h2>
                <p class="text-sm text-gray-500">
                    Use this for expenses or income not generated automatically
                </p>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">
                        Transaction type
                    </label>
                    <select wire:model="txType"
                            class="w-full border rounded px-3 py-2 text-sm">
                        <option value="expense">Money out (expense)</option>
                        <option value="income">Money in (income)</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">
                        Category
                    </label>
                    <input wire:model.defer="txCategory"
                           class="w-full border rounded px-3 py-2 text-sm"
                           placeholder="Electricity, Maintenance, Other income" />
                </div>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">
                        Amount
                    </label>
                    <input wire:model.defer="txAmount"
                           type="number"
                           class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">
                        Transaction date
                    </label>
                    <input wire:model.defer="txDate"
                           type="date"
                           class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="text-xs text-gray-500 mb-1 block">
                        Note (optional)
                    </label>
                    <textarea wire:model.defer="txNote"
                              rows="2"
                              class="w-full border rounded px-3 py-2 text-sm"
                              placeholder="Example: paid electricity bill for July">
                    </textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button wire:click="closeTransactionModal"
                            class="text-sm text-gray-600">
                        Cancel
                    </button>

                    <button wire:click="saveTransaction"
                            class="px-4 py-2 bg-gray-900 text-white text-sm rounded">
                        Save record
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
