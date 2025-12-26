<div class="px-6 py-6 space-y-6">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-sm text-gray-500">
                Overview of rent, payments, and unit status
            </p>
        </div>

        {{-- RANGE SELECTOR --}}
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

    <p class="text-xs text-gray-400">
        All numbers below are based on the selected period
    </p>

    {{-- =====================================================
        CRITICAL ALERT
    ====================================================== --}}
    @if ($overdueCount > 0)
        <section class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="font-semibold text-red-700">
                {{ $overdueCount }} unpaid rent {{ $overdueCount > 1 ? 'invoices' : 'invoice' }}
            </p>
            <p class="text-sm text-red-600">
                These payments are overdue and need immediate attention
            </p>

            <a href="/rent/unpaid"
               class="inline-block mt-2 text-sm text-red-700 font-medium underline">
                Review unpaid rents →
            </a>
        </section>
    @endif

    {{-- =====================================================
        FINANCIAL SNAPSHOT
    ====================================================== --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Money Received</p>
            <p class="text-2xl font-bold text-green-600">
                Rp {{ number_format($summary['income'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Payments received in this period
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Money Spent</p>
            <p class="text-2xl font-bold text-red-600">
                Rp {{ number_format($summary['expense'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Operational expenses
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl border">
            <p class="text-xs text-gray-500">Balance</p>
            <p class="text-2xl font-bold">
                Rp {{ number_format($summary['balance'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Remaining balance after expenses
            </p>
        </div>

    </section>

    {{-- =====================================================
        UNIT STATUS
    ====================================================== --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border">
            <p class="text-xs text-gray-500">Occupied Units</p>
            <p class="text-xl font-bold text-red-600">
                {{ $unitStats['occupied'] }}
            </p>
            <p class="text-xs text-gray-400">
                Currently rented
            </p>
        </div>

        <div class="bg-white p-4 rounded-xl border">
            <p class="text-xs text-gray-500">Available Units</p>
            <p class="text-xl font-bold text-green-600">
                {{ $unitStats['available'] }}
            </p>
            <p class="text-xs text-gray-400">
                Ready for new tenants
            </p>
        </div>

        <div class="bg-white p-4 rounded-xl border">
            <p class="text-xs text-gray-500">Inactive Units</p>
            <p class="text-xl font-bold text-gray-600">
                {{ $unitStats['inactive'] }}
            </p>
            <p class="text-xs text-gray-400">
                Under maintenance or unavailable
            </p>
        </div>
    </section>

    {{-- =====================================================
        MAIN GRID
    ====================================================== --}}
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- RENT FOLLOW UP --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Rent Follow-Up</h2>
                    <p class="text-sm text-gray-500">
                        Tenants who require payment attention
                    </p>
                </div>

                <a href="/rent/unpaid"
                   class="text-sm text-red-600 hover:underline">
                    See all →
                </a>
            </div>

            @forelse ($rentAlerts as $alert)
                <div class="flex items-center justify-between border rounded-lg p-3">
                    <div>
                        <p class="text-sm font-medium">
                            {{ $alert['tenant'] }} — {{ $alert['unit'] }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Due {{ \Carbon\Carbon::parse($alert['due_date'])->format('d M Y') }}
                            · Rp {{ number_format($alert['amount']) }}
                        </p>
                    </div>

                    <span
                        class="px-2 py-0.5 text-xs font-semibold rounded
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
                        @if ($alert['label'] === 'OVERDUE')
                            Overdue
                        @elseif ($alert['label'] === 'H')
                            Due today
                        @else
                            Due in {{ substr($alert['label'], 2) }} days
                        @endif
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    All rent payments are under control 👍
                </p>
            @endforelse
        </div>

        {{-- RECENT ACTIVITY --}}
        <div class="bg-white rounded-xl border p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Recent Activity</h2>
                    <p class="text-sm text-gray-500">
                        Latest income and expenses
                    </p>
                </div>

                <a href="/ledger"
                   class="text-sm text-blue-600 hover:underline">
                    View full ledger →
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($transactions as $t)
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium">
                                {{ $t['category'] }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $t['date'] }}
                                @if ($t['note'])
                                    · {{ $t['note'] }}
                                @endif
                            </p>
                        </div>

                        <div class="text-sm font-semibold
                            {{ $t['type'] === 'expense'
                                ? 'text-red-600'
                                : 'text-green-600' }}">
                            {{ $t['type'] === 'expense' ? '–' : '+' }}
                            Rp {{ number_format($t['amount']) }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        No recent activity
                    </p>
                @endforelse
            </div>
        </div>

    </section>

</div>
