<?php

namespace App\Services;

use App\Models\RentBilling;
use App\Models\RentCycle;
use Carbon\Carbon;

class RentBillingService
{
    /* ======================================================
        MONTHLY BILLING — SINGLE MONTH
    ====================================================== */

    public function summaryForMonth(RentCycle $rent, Carbon $month): array
    {
        $billings = RentBilling::query()
            ->where('rent_cycle_id', $rent->id)
            ->whereMonth('billing_month', $month->month)
            ->whereYear('billing_month', $month->year)
            ->get();

        $expected = $billings->sum('amount');

        $paid = $billings
            ->whereNotNull('paid_at')
            ->sum('amount');

        $outstanding = $billings
            ->whereNull('paid_at')
            ->sum('amount');

        return [
            'expected'    => $expected,
            'paid'        => $paid,
            'outstanding' => $outstanding,
            'status'      => $outstanding === 0 ? 'paid' : 'unpaid',
        ];
    }

    /* ======================================================
        WHO HASN’T PAID — UNPAID LIST (CORE)
    ====================================================== */

    public function unpaidRentsForMonth(Carbon $month)
    {
        return RentCycle::query()
            ->whereNull('end_date')
            ->with(['unit', 'tenant'])
            ->get()
            ->map(function (RentCycle $rent) use ($month) {

                $billings = RentBilling::query()
                    ->where('rent_cycle_id', $rent->id)
                    ->whereMonth('billing_month', $month->month)
                    ->whereYear('billing_month', $month->year)
                    ->get();

                $expected = $billings->sum('amount');
                $paid     = $billings->whereNotNull('paid_at')->sum('amount');
                $outstanding = $billings->whereNull('paid_at')->sum('amount');

                return [
                    'rent'        => $rent,
                    'unit'        => $rent->unit,
                    'tenant'      => $rent->tenant,
                    'expected'    => $expected,
                    'paid'        => $paid,
                    'outstanding' => $outstanding,
                ];
            })
            ->filter(fn ($row) => $row['outstanding'] > 0)
            ->values();
    }

    /* ======================================================
        HISTORICAL / CARRY — ALL TIME UNTIL MONTH
    ====================================================== */

    public function summaryWithCarry(RentCycle $rent, Carbon $month): array
    {
        $billings = RentBilling::query()
            ->where('rent_cycle_id', $rent->id)
            ->whereDate(
                'billing_month',
                '<=',
                $month->copy()->endOfMonth()
            )
            ->get();

        $expected = $billings->sum('amount');

        $paid = $billings
            ->whereNotNull('paid_at')
            ->sum('amount');

        $outstanding = $billings
            ->whereNull('paid_at')
            ->sum('amount');

        return [
            'expected_total' => $expected,
            'paid_total'     => $paid,
            'credit'         => max(0, $paid - $expected),
            'debt'           => max(0, $expected - $paid),
            'status'         => $outstanding === 0 ? 'paid' : 'unpaid',
        ];
    }
}
