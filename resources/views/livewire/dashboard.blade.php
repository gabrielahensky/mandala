<div class="p-10 space-y-10">

    <!-- TITLE -->
    <h1 class="text-2xl font-bold">
        Mandala — Financial Overview
    </h1>

    <!-- DATE RANGE TOGGLE -->
    <div class="flex gap-2">
        <button
            wire:click="changeRange('this_month')"
            class="px-3 py-1 rounded
                   {{ $range === 'this_month'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-200 text-gray-800' }}">
            This Month
        </button>

        <button
            wire:click="changeRange('last_month')"
            class="px-3 py-1 rounded
                   {{ $range === 'last_month'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-200 text-gray-800' }}">
            Last Month
        </button>
    </div>

    <!-- ADD TRANSACTION -->
    <section class="bg-white p-6 rounded-lg shadow-sm space-y-4">
        <h2 class="font-semibold">
            Add Transaction
        </h2>

        <form wire:submit.prevent="addTransaction"
              class="flex flex-wrap gap-3">

            <select wire:model="type"
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
    <section class="flex gap-5">
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

        <div class="bg-white rounded-lg overflow-hidden shadow-sm">
            <table class="w-full text-sm">
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
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $t['date'] }}</td>
                            <td class="px-4 py-2">
                                {{ strtoupper($t['type']) }}
                            </td>
                            <td class="px-4 py-2">{{ $t['category'] }}</td>
                            <td class="px-4 py-2 text-right
                                {{ $t['type'] === 'expense'
                                    ? 'text-red-600'
                                    : 'text-green-600' }}">
                                Rp {{ number_format($t['amount']) }}
                            </td>
                            <td class="px-4 py-2">{{ $t['note'] }}</td>
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
    <section class="grid grid-cols-2 gap-10">

        <!-- Income Breakdown -->
        <div class="space-y-2">
            <h3 class="font-semibold">
                Income by Category
            </h3>

            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($incomeByCategory as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row['category'] }}</td>
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
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row['category'] }}</td>
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
