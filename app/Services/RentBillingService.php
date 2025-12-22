<?php

namespace App\Services;

use App\Models\RentCycle;
use App\Models\Transaction;
use Carbon\Carbon;

class RentBillingService
{
    /* =========================
        MONTHLY (STRICT)
       ========================= */

    protected function expectedForMonth(RentCycle $rent, Carbon $month): int
    {
        if (!$rent->monthly_rent) {
            return 0;
        }

        $start = Carbon::parse($rent->start_date)->startOfMonth();
        $target = $month->copy()->startOfMonth();

        // belum mulai sewa
        if ($target->lessThan($start)) {
            return 0;
        }

        return (int) $rent->monthly_rent;
    }

    protected function paidForMonth(RentCycle $rent, Carbon $month): int
    {
        return Transaction::query()
            ->where('type', 'income')
            ->where('category', 'Rent')
            ->forTenant($rent->tenant_id)
            ->forUnit($rent->unit_id)
            ->whereBetween(
                'transacted_at',
                [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ]
            )
            ->sum('amount');
    }

    public function summaryForMonth(RentCycle $rent, Carbon $month): array
    {
        $expected = $this->expectedForMonth($rent, $month);
        $paid     = $this->paidForMonth($rent, $month);

        return [
            'expected'    => $expected,
            'paid'        => $paid,
            'outstanding' => max(0, $expected - $paid),
            'overpaid'    => max(0, $paid - $expected),
            'status'      => $paid >= $expected ? 'paid' : 'unpaid',
        ];
    }

    /* =========================
        CARRY / HISTORICAL
       ========================= */

    protected function totalPaidUntil(RentCycle $rent, Carbon $month): int
    {
        return Transaction::query()
            ->where('type', 'income')
            ->where('category', 'Rent')
            ->forTenant($rent->tenant_id)
            ->forUnit($rent->unit_id)
            ->whereDate('transacted_at', '<=', $month->copy()->endOfMonth())
            ->sum('amount');
    }

    protected function expectedUntil(RentCycle $rent, Carbon $month): int
    {
        if (!$rent->monthly_rent) {
            return 0;
        }

        $start = Carbon::parse($rent->start_date)->startOfMonth();
        $end   = $month->copy()->startOfMonth();

        if ($end->lessThan($start)) {
            return 0;
        }

        $months = $start->diffInMonths($end) + 1;

        return $months * $rent->monthly_rent;
    }

    public function summaryWithCarry(RentCycle $rent, Carbon $month): array
    {
        $paid     = $this->totalPaidUntil($rent, $month);
        $expected = $this->expectedUntil($rent, $month);

        $balance = $paid - $expected;

        return [
            'expected_total' => $expected,
            'paid_total'     => $paid,
            'credit'         => max(0, $balance),
            'debt'           => max(0, -$balance),
            'status'         => $balance >= 0 ? 'paid' : 'unpaid',
        ];
    }

    /* =========================
        WHO HASN’T PAID (MONTHLY)
       ========================= */
    public function unpaidRentsForMonth(Carbon $month)
    {
        return RentCycle::query()
            ->whereNull('end_date')
            ->with(['unit', 'tenant'])
            ->get()
            ->map(function ($rent) use ($month) {
                $expected = $rent->monthly_rent ?? 0;
       
                $paid = Transaction::query()
                    ->where('type', 'income')
                    ->where('category', 'Rent')
                    ->forTenant($rent->tenant_id)
                    ->forUnit($rent->unit_id)
                    ->whereBetween(
                        'transacted_at',
                        [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]
                    )
                    ->sum('amount');
       
                $outstanding = max(0, $expected - $paid);
       
                return [
                    'rent_id'     => $rent->id,
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
       
    public function unpaidSummaryForMonth(Carbon $month)
    {
        return RentCycle::query()
            ->whereNull('end_date')
            ->with(['unit', 'tenant'])
            ->get()
            ->map(function ($rent) use ($month) {
                $monthly = $this->summaryForMonth($rent, $month);

                if ($monthly['status'] !== 'unpaid') {
                    return null;
                }

                return [
                    'rent_id'     => $rent->id,
                    'unit'        => $rent->unit->name,
                    'tenant'      => $rent->tenant->name,
                    'expected'    => $monthly['expected'],
                    'paid'        => $monthly['paid'],
                    'outstanding' => $monthly['outstanding'],
                ];
            })
            ->filter()
            ->values();
    }
}
