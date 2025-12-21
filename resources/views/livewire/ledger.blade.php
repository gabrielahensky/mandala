<div class="px-4 py-6 sm:px-8 sm:py-10 space-y-6">

    <!-- HEADER -->
    <div>
        <h1 class="text-2xl font-bold">
            Ledger
        </h1>
        <p class="text-sm text-gray-500">
            Financial records • {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
        </p>
    </div>

    <!-- FILTER BAR -->
    <div class="flex flex-wrap gap-2">

        <input
            type="month"
            wire:model.defer="month"
            wire:change="refreshLedger"
            class="border rounded px-3 py-2 text-sm"
        />

        <select
            wire:model="filterUnitId"
            wire:change="refreshLedger"
            class="border rounded px-3 py-2 text-sm"
        >
            <option value="">All Units</option>
            @foreach ($this->units as $unit)
                <option value="{{ $unit->id }}">
                    {{ $unit->name }}
                </option>
            @endforeach
        </select>

        <select
            wire:model="filterTenantId"
            wire:change="refreshLedger"
            class="border rounded px-3 py-2 text-sm"
        >
            <option value="">All Tenants</option>
            @foreach ($this->tenants as $tenant)
                <option value="{{ $tenant->id }}">
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>

        @if ($filterUnitId || $filterTenantId)
            <button
                wire:click="
                    $set('filterUnitId', null);
                    $set('filterTenantId', null);
                    refreshLedger();
                "
                class="text-xs text-gray-500 hover:underline"
            >
                Clear filters
            </button>
        @endif
    </div>

    <!-- LEDGER TABLE -->
    <div class="overflow-x-auto bg-white rounded-xl border">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Category</th>
                    <th class="px-4 py-2 text-left">Note</th>
                    <th class="px-4 py-2 text-right">Income</th>
                    <th class="px-4 py-2 text-right">Expense</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($transactions as $t)
                    <tr class="border-t hover:bg-gray-50">
                        <!-- DATE -->
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ $t->transacted_at->format('d M') }}
                        </td>

                        <!-- CATEGORY + BADGES -->
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span>{{ $t->category }}</span>

                                @if ($t->corrections_count > 0)
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-yellow-100 text-yellow-700">
                                        Corrected
                                    </span>
                                @endif

                                @if ($t->evidences_count > 0)
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-600">
                                        Evidence
                                    </span>
                                @endif

                                @if ($t->hasUnit())
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-blue-100 text-blue-700">
                                        Unit
                                    </span>
                                @endif

                                @if ($t->hasTenant())
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-purple-100 text-purple-700">
                                        Tenant
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- NOTE -->
                        <td class="px-4 py-2 text-gray-500">
                            {{ $t->note }}

                            @if ($t->isCorrection() && $t->original)
                                <div class="text-xs text-gray-400 mt-1">
                                    Correction of {{ $t->original->transacted_at->format('d M Y') }}
                                </div>
                            @endif
                        </td>

                        <!-- INCOME -->
                        <td class="px-4 py-2 text-right text-green-600">
                            @if ($t->type === 'income')
                                Rp {{ number_format($t->amount) }}
                            @endif
                        </td>

                        <!-- EXPENSE -->
                        <td class="px-4 py-2 text-right text-red-600">
                            @if ($t->type === 'expense')
                                Rp {{ number_format($t->amount) }}
                            @endif
                        </td>

                        <!-- ACTION -->
                        <td class="px-4 py-2 text-right">
                            @if (!$t->isCorrection())
                                <button
                                    wire:click="openCorrection({{ $t->id }})"
                                    class="text-xs text-blue-600 hover:underline">
                                    Correct
                                </button>
                            @else
                                <span class="text-xs text-gray-400">
                                    Correction
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            No transactions for this month
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- =========================
        CORRECTION MODAL
    ========================== --}}
    @if ($showCorrectionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-5 space-y-4">

                <h2 class="text-lg font-semibold">
                    Correct Transaction
                </h2>

                <p class="text-sm text-gray-600">
                    This will create a new transaction to correct the selected one.
                </p>

                @if ($correctionTarget)
                    <div class="rounded-lg border bg-gray-50 p-3 text-sm space-y-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">
                            Original Transaction
                        </p>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Date</span>
                            <span>{{ $correctionTarget->transacted_at->format('d M Y') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Type</span>
                            <span class="{{ $correctionTarget->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ strtoupper($correctionTarget->type) }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-600">Category</span>
                            <span>{{ $correctionTarget->category }}</span>
                        </div>

                        <div class="flex justify-between font-medium">
                            <span class="text-gray-600">Amount</span>
                            <span>Rp {{ number_format($correctionTarget->amount) }}</span>
                        </div>

                        @if ($correctionTarget->note)
                            <div class="pt-1 text-xs text-gray-500">
                                Note: {{ $correctionTarget->note }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600">
                            Correction amount
                        </label>
                        <input
                            type="number"
                            wire:model="correctionAmount"
                            min="1"
                            class="w-full border rounded px-3 py-2 text-sm"
                        />
                        @error('correctionAmount')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">
                            Reason (required)
                        </label>
                        <textarea
                            wire:model="correctionNote"
                            rows="3"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Explain why this correction is needed"
                        ></textarea>
                        @error('correctionNote')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        wire:click="closeCorrection"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        wire:click="submitCorrection"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded">
                        Confirm correction
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
