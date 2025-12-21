<div style="padding:40px">

    <!-- TITLE -->
    <h1 style="font-size:24px; font-weight:bold; margin-bottom:20px">
        Mandala — Financial Overview
    </h1>

    <!-- DATE RANGE TOGGLE -->
    <div style="margin-bottom:20px">
        <button
            wire:click="changeRange('this_month')"
            style="padding:6px 12px; margin-right:6px;
                background:{{ $range === 'this_month' ? '#111' : '#e5e7eb' }};
                color:{{ $range === 'this_month' ? '#fff' : '#000' }};">
            This Month
        </button>

        <button
            wire:click="changeRange('last_month')"
            style="padding:6px 12px;
                background:{{ $range === 'last_month' ? '#111' : '#e5e7eb' }};
                color:{{ $range === 'last_month' ? '#fff' : '#000' }};">
            Last Month
        </button>
    </div>

    <!-- ADD TRANSACTION -->
    <div style="margin-bottom:30px; background:white; padding:20px; border-radius:8px">
        <h2 style="font-weight:bold; margin-bottom:10px">
            Add Transaction
        </h2>

        <form wire:submit.prevent="addTransaction"
              style="display:flex; gap:10px; flex-wrap:wrap">

            <select wire:model="type" style="padding:8px">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>

            <input
                type="text"
                wire:model="category"
                placeholder="Category"
                style="padding:8px"
                required
            />

            <input
                type="number"
                wire:model="amount"
                placeholder="Amount"
                style="padding:8px"
                required
            />

            <input
                type="date"
                wire:model="transacted_at"
                style="padding:8px"
                required
            />

            <input
                type="text"
                wire:model="note"
                placeholder="Note (optional)"
                style="padding:8px; flex:1"
            />

            <button
                type="submit"
                style="padding:8px 16px; background:black; color:white; border:none">
                Add
            </button>

        </form>
    </div>

    <!-- SUMMARY -->
    <div style="display:flex; gap:20px">

        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Income</p>
            <p style="font-size:20px; font-weight:bold; color:green">
                Rp {{ number_format($summary['income'] ?? 0) }}
            </p>
        </div>

        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Expense</p>
            <p style="font-size:20px; font-weight:bold; color:red">
                Rp {{ number_format($summary['expense'] ?? 0) }}
            </p>
        </div>

        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Balance</p>
            <p style="font-size:20px; font-weight:bold">
                Rp {{ number_format($summary['balance'] ?? 0) }}
            </p>
        </div>

    </div>

    <!-- RECENT TRANSACTIONS -->
    <div style="margin-top:40px">
        <h2 style="font-size:18px; font-weight:bold; margin-bottom:12px">
            Recent Transactions
        </h2>

        <div style="background:white; border-radius:8px; overflow:hidden">
            <table style="width:100%; border-collapse:collapse">
                <thead style="background:#f9fafb">
                    <tr>
                        <th style="padding:10px; text-align:left">Date</th>
                        <th style="padding:10px; text-align:left">Type</th>
                        <th style="padding:10px; text-align:left">Category</th>
                        <th style="padding:10px; text-align:right">Amount</th>
                        <th style="padding:10px; text-align:left">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr style="border-top:1px solid #eee">
                            <td style="padding:10px">{{ $t['date'] }}</td>
                            <td style="padding:10px">
                                {{ strtoupper($t['type']) }}
                            </td>
                            <td style="padding:10px">{{ $t['category'] }}</td>
                            <td style="padding:10px; text-align:right;
                                color:{{ $t['type'] === 'expense' ? 'red' : 'green' }}">
                                Rp {{ number_format($t['amount']) }}
                            </td>
                            <td style="padding:10px">{{ $t['note'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:10px; color:#6b7280">
                                No transactions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- BREAKDOWN -->
    <div style="margin-top:40px; display:flex; gap:40px">

        <!-- Income Breakdown -->
        <div style="flex:1">
            <h3 style="font-size:16px; font-weight:bold; margin-bottom:10px">
                Income by Category
            </h3>

            <div style="background:white; border-radius:8px; overflow:hidden">
                <table style="width:100%">
                    <tbody>
                        @forelse ($incomeByCategory as $row)
                            <tr style="border-top:1px solid #eee">
                                <td style="padding:10px">{{ $row['category'] }}</td>
                                <td style="padding:10px; text-align:right; color:green">
                                    Rp {{ number_format($row['total']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="padding:10px; color:#6b7280">
                                    No income data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div style="flex:1">
            <h3 style="font-size:16px; font-weight:bold; margin-bottom:10px">
                Expense by Category
            </h3>

            <div style="background:white; border-radius:8px; overflow:hidden">
                <table style="width:100%">
                    <tbody>
                        @forelse ($expenseByCategory as $row)
                            <tr style="border-top:1px solid #eee">
                                <td style="padding:10px">{{ $row['category'] }}</td>
                                <td style="padding:10px; text-align:right; color:red">
                                    Rp {{ number_format($row['total']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="padding:10px; color:#6b7280">
                                    No expense data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
