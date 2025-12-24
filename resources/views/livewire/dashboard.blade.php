<div class="px-6 py-6 space-y-10">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Dashboard
            </h1>
            <p class="text-sm text-gray-500">
                Financial overview & active rent monitoring
            </p>
        </div>

        {{-- RANGE --}}
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
    </div>

    {{-- OVERDUE ALERT --}}
    @if ($overdueCount > 0)
        <section class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="font-semibold text-red-700">
                {{ $overdueCount }} rent invoice overdue
            </p>
            <p class="text-sm text-red-600">
                Immediate action required to prevent accumulation
            </p>
        </section>
    @endif

    {{-- HEALTH SNAPSHOT --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Total Income</p>
            <p class="text-2xl font-bold text-green-600">
                Rp {{ number_format($summary['income'] ?? 0) }}
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Total Expense</p>
            <p class="text-2xl font-bold text-red-600">
                Rp {{ number_format($summary['expense'] ?? 0) }}
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Net Balance</p>
            <p class="text-2xl font-bold">
                Rp {{ number_format($summary['balance'] ?? 0) }}
            </p>
        </div>

    </section>

    {{-- RENT ATTENTION LOG --}}
    <section class="bg-white rounded-xl border p-5 space-y-4">
        <div>
            <h2 class="text-lg font-semibold">
                Rent Attention Log
            </h2>
            <p class="text-sm text-gray-500">
                Upcoming, due, and overdue rent invoices
            </p>
        </div>

        @forelse ($rentAlerts as $alert)
            <div class="flex items-center justify-between border rounded-lg p-3">

                <div>
                    <p class="font-medium">
                        {{ $alert['tenant'] }} — {{ $alert['unit'] }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Due {{ \Carbon\Carbon::parse($alert['due_date'])->format('d M Y') }}
                        · Rp {{ number_format($alert['amount']) }}
                    </p>
                </div>

                @if ($alert['label'])
                    <span
                        class="px-3 py-1 text-xs font-semibold rounded
                        @class([
                            'bg-gray-100 text-gray-600' =>
                                str_starts_with($alert['label'], 'H-')
                                && (int) substr($alert['label'], 2) >= 5,

                            'bg-yellow-100 text-yellow-700' =>
                                str_starts_with($alert['label'], 'H-')
                                && (int) substr($alert['label'], 2) <= 4,

                            'bg-orange-100 text-orange-700' =>
                                $alert['label'] === 'H',

                            'bg-red-100 text-red-700' =>
                                $alert['label'] === 'OVERDUE',
                        ])">
                        {{ $alert['label'] }}
                    </span>
                @endif

            </div>
        @empty
            <p class="text-sm text-gray-500">
                All rent invoices are under control 🎉
            </p>
        @endforelse
    </section>

    {{-- RECENT TRANSACTIONS --}}
    <section class="space-y-3">
        <h2 class="text-lg font-semibold">
            Recent Transactions
        </h2>

        <div class="bg-white rounded-xl border overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Date</th>
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
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                No transactions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- CATEGORY SNAPSHOT --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white rounded-xl border p-4">
            <h3 class="font-semibold mb-3">
                Income Breakdown
            </h3>

            @forelse ($incomeByCategory as $row)
                <div class="flex justify-between text-sm py-1">
                    <span>{{ $row['category'] }}</span>
                    <span class="text-green-600">
                        Rp {{ number_format($row['total']) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No income data
                </p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border p-4">
            <h3 class="font-semibold mb-3">
                Expense Breakdown
            </h3>

            @forelse ($expenseByCategory as $row)
                <div class="flex justify-between text-sm py-1">
                    <span>{{ $row['category'] }}</span>
                    <span class="text-red-600">
                        Rp {{ number_format($row['total']) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No expense data
                </p>
            @endforelse
        </div>

    </section>

    {{-- ADD TRANSACTION (DE-EMPHASIZED) --}}
    <section class="bg-white/60 border rounded-xl p-5">
        <details>
            <summary class="cursor-pointer font-semibold text-sm text-gray-600">
                + Add Transaction
            </summary>

            <form
                wire:submit.prevent="addTransaction"
                class="flex flex-wrap gap-3 mt-4">

                <select wire:model="type" class="border rounded px-3 py-2">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>

                <input wire:model="category" placeholder="Category"
                       class="border rounded px-3 py-2" required />

                <input wire:model="amount" type="number"
                       class="border rounded px-3 py-2" required />

                <input wire:model="transacted_at" type="date"
                       class="border rounded px-3 py-2" required />

                <input wire:model="note" placeholder="Note"
                       class="border rounded px-3 py-2 flex-1" />

                <button class="px-4 py-2 bg-gray-900 text-white rounded">
                    Add
                </button>
            </form>
        </details>
    </section>

</div>
