<?php

namespace App\Services;

use App\Models\RentCycle;
use App\Models\Unit;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RentCycleService
{
    /**
     * Start a new rent cycle
     */
    public function startRent(
        Unit $unit,
        Tenant $tenant,
        Carbon|string|null $startDate = null,
        ?int $monthlyRent = null,
        ?string $note = null
    ): RentCycle {
        $startDate = $startDate
            ? Carbon::parse($startDate)
            : now();

        // RULE: unit must be free
        if ($unit->activeRent) {
            throw ValidationException::withMessages([
                'unit' => 'Unit is already occupied.',
            ]);
        }

        // RULE: tenant must not have active rent
        if ($tenant->activeRent) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant already has an active rent.',
            ]);
        }

        // SNAPSHOT PRICE
        $finalMonthlyRent = $monthlyRent ?? $unit->base_price;

        if ($finalMonthlyRent === null) {
            throw ValidationException::withMessages([
                'monthly_rent' => 'Monthly rent cannot be null.',
            ]);
        }

        // CREATE RENT CYCLE
        $rent = RentCycle::create([
            'unit_id'      => $unit->id,
            'tenant_id'    => $tenant->id,
            'start_date'   => $startDate,
            'monthly_rent' => $finalMonthlyRent,
            'note'         => $note,
        ]);

        // CREATE FIRST INVOICE (MONTH OF START)
        app(RentInvoiceService::class)
            ->generateForRent(
                $rent,
                $startDate->copy()->startOfMonth()
            );

        return $rent;
    }

    /**
     * End rent cycle safely
     */
    public function endRentSafely(
        RentCycle $rent,
        Carbon|string|null $endDate = null,
        ?string $note = null
    ): RentCycle {
        if (! $rent->isActive()) {
            throw ValidationException::withMessages([
                'rent' => 'Rent cycle already ended.',
            ]);
        }

        $endDate = $endDate
            ? Carbon::parse($endDate)
            : now();

        // SAFETY: cannot end before start
        if ($endDate->lt($rent->start_date)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date cannot be before start date.',
            ]);
        }

        // SAFETY: no unpaid invoices
        $hasUnpaidInvoices = $rent->billings()
            ->where('status', '!=', 'paid')
            ->exists();

        if ($hasUnpaidInvoices) {
            throw ValidationException::withMessages([
                'rent' => 'Cannot end rent while unpaid invoices exist.',
            ]);
        }

        $rent->update([
            'end_date' => $endDate,
            'note' => $note
                ? trim(($rent->note ?? '') . "\nEnd: " . $note)
                : $rent->note,
        ]);

        return $rent;
    }
}
