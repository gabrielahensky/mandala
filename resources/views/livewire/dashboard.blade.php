<div class="px-4 py-6 sm:px-8 sm:py-10 space-y-12">

    <!-- HEADER -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">
                Mandala
            </h1>
            <p class="text-sm text-gray-500">
                Financial Overview
            </p>
        </div>

        <!-- DATE RANGE TOGGLE -->
        <div class="flex flex-col items-end gap-1">
            <div class="flex gap-1 bg-gray-200 p-1 rounded-lg">
                <button
                    wire:click="changeRange('this_month')"
                    class="px-3 py-1 rounded-md text-sm
                        {{ $range === 'this_month'
                            ? 'bg-white shadow text-gray-900'
                            : 'text-gray-600' }}">
                    This Month
                </button>

                <button
                    wire:click="changeRange('last_month')"
                    class="px-3 py-1 rounded-md text-sm
                        {{ $range === 'last_month'
                            ? 'bg-white shadow text-gray-900'
                            : 'text-gray-600' }}">
                    Last Month
                </button>
            </div>

            <span
                wire:loading
                wire:target="range"
                class="text-xs text-gray-400">
                Updating…
            </span>
        </div>
    </div>

    <!-- ADD TRANSACTION -->
    <section class="bg-white/60 backdrop-blur p-5 rounded-xl border space-y-4">
        <h2 class="text-sm font-semibold text-gray-600">
            Add Transaction
        </h2>

        <form
            wire:submit.prevent="addTransaction"
            class="flex flex-wrap gap-3">

            <select
                wire:model="type"
                class="border rounded px-3 py-2">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>

            <input
                type="text"
                wire:model="category"
                placeholder="Category"
                class="border rounded px-3 py-2"
                required
            />

            <input
                type="number"
                wire:model="amount"
                placeholder="Amount"
                class="border rounded px-3 py-2"
                required
            />

            <input
                type="date"
                wire:model="transacted_at"
                class="border rounded px-3 py-2"
                required
            />

            <input
                type="text"
                wire:model="note"
                placeholder="Note (optional)"
                class="border rounded px-3 py-2 flex-1"
            />

            <button
                type="submit"
                class="px-4 py-2 bg-gray-900 text-white rounded">
                Add
            </button>
        </form>
    </section>

    <!-- SUMMARY -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-summary-card
            label="Income"
            :value="$summary['income'] ?? 0"
            color="text-green-600"
        />

        <x-summary-card
            label="Expense"
            :value="$summary['expense'] ?? 0"
            color="text-red-600"
        />

        <x-summary-card
            label="Balance"
            :value="$summary['balance'] ?? 0"
            color="text-gray-900"
        />
    </section>

    <!-- RECENT TRANSACTIONS -->
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">
            Recent Transactions
        </h2>

        <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
            <table class="min-w-[700px] w-full text-sm">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-xs text-gray-500">
                                {{ $t['date'] }}
                            </td>
                            <td class="px-4 py-2">
                                {{ strtoupper($t['type']) }}
                            </td>
                            <td class="px-4 py-2">
                                {{ $t['category'] }}
                            </td>
                            <td class="px-4 py-2 text-right
                                {{ $t['type'] === 'expense'
                                    ? 'text-red-600'
                                    : 'text-green-600' }}">
                                Rp {{ number_format($t['amount']) }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $t['note'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="px-4 py-4 text-gray-500">
                                No transactions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- BREAKDOWN -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <!-- Income Breakdown -->
        <div class="space-y-2">
            <h3 class="font-semibold">
                Income by Category
            </h3>

            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($incomeByCategory as $row)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    {{ $row['category'] }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    Rp {{ number_format($row['total']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2"
                                    class="px-4 py-4 text-gray-500">
                                    No income data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="space-y-2">
            <h3 class="font-semibold">
                Expense by Category
            </h3>

            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($expenseByCategory as $row)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    {{ $row['category'] }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    Rp {{ number_format($row['total']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2"
                                    class="px-4 py-4 text-gray-500">
                                    No expense data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </section>

</div>
