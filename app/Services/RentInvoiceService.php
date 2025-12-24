<?php

namespace App\Services;

use App\Models\RentBilling;
use App\Models\RentCycle;
use Carbon\Carbon;

class RentInvoiceService
{
    /**
     * Generate invoice untuk satu bulan (idempotent)
     */
    public function generateForMonth(Carbon|string $month): void
    {
        $month = Carbon::parse($month)->startOfMonth();

        // semua rent aktif yang sudah mulai sebelum / di bulan ini
        $rents = RentCycle::query()
            ->whereNull('end_date')
            ->whereDate('start_date', '<=', $month->endOfMonth())
            ->get();

        foreach ($rents as $rent) {
            $this->generateForRent($rent, $month);
        }
    }

    /**
     * Generate invoice untuk satu rent & bulan
     */
    public function generateForRent(RentCycle $rent, Carbon $month): ?RentBilling
    {
        // cegah double
        if (
            RentBilling::where('rent_cycle_id', $rent->id)
                ->whereDate('billing_month', $month)
                ->exists()
        ) {
            return null;
        }

        // anchor: tanggal masuk tenant
        $anchorDay = $rent->start_date->day;

        // handle bulan pendek (28/29/30)
        $dueDate = $month->copy()->day(
            min($anchorDay, $month->daysInMonth)
        );

        $status = now()->lt($dueDate)
            ? 'upcoming'
            : (now()->isSameDay($dueDate) ? 'due' : 'overdue');

        return RentBilling::create([
            'rent_cycle_id' => $rent->id,
            'billing_month' => $month,
            'amount' => $rent->monthly_rent,
            'due_date' => $dueDate,
            'status' => $status,
        ]);
    }

    /**
     * Update status invoice harian (due / overdue)
     */
    public function refreshStatuses(): void
    {
        RentBilling::whereNull('paid_at')->get()->each(function ($bill) {
            if (now()->gt($bill->due_date)) {
                $bill->update(['status' => 'overdue']);
            } elseif (now()->isSameDay($bill->due_date)) {
                $bill->update(['status' => 'due']);
            }
        });
    }
}
