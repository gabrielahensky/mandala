<div style="padding:40px">
    <h1 style="font-size:24px; font-weight:bold; margin-bottom:20px">
        Mandala — Financial Overview
    </h1>

    <div style="display:flex; gap:20px">
        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Income</p>
            <p style="font-size:20px; font-weight:bold; color:green">
                Rp {{ number_format($summary['income']) }}
            </p>
        </div>

        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Expense</p>
            <p style="font-size:20px; font-weight:bold; color:red">
                Rp {{ number_format($summary['expense']) }}
            </p>
        </div>

        <div style="background:white; padding:20px; border-radius:8px; width:200px">
            <p style="color:#6b7280">Balance</p>
            <p style="font-size:20px; font-weight:bold">
                Rp {{ number_format($summary['balance']) }}
            </p>
        </div>
    </div>

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
                    @foreach ($transactions as $t)
                        <tr style="border-top:1px solid #eee">
                            <td style="padding:10px">{{ $t['date'] }}</td>
                            <td style="padding:10px">
                                {{ strtoupper($t['type']) }}
                            </td>
                            <td style="padding:10px">{{ $t['category'] }}</td>
                            <td style="padding:10px; text-align:right; color:{{ $t['type'] === 'expense' ? 'red' : 'green' }}">
                                Rp {{ number_format($t['amount']) }}
                            </td>
                            <td style="padding:10px">{{ $t['note'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
