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
     * Start new rent cycle
     */
    public function startRent(
        Unit $unit,
        Tenant $tenant,
        Carbon|string $startDate = null,
        ?int $monthlyRent = null,
        ?string $note = null
    ): RentCycle {
        // normalize date
        $startDate = $startDate
            ? Carbon::parse($startDate)
            : now();

        // RULE 1: unit must be free
        if ($unit->activeRent) {
            throw ValidationException::withMessages([
                'unit' => 'Unit is already occupied.',
            ]);
        }

        // RULE 2: tenant must not have active rent
        if ($tenant->activeRent) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant already has an active rent.',
            ]);
        }

        return RentCycle::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => $startDate,
            'monthly_rent' => $monthlyRent,
            'note' => $note,
        ]);
    }

    /**
     * End active rent cycle
     */
    public function endRent(
        RentCycle $rentCycle,
        Carbon|string $endDate = null,
        ?string $note = null
    ): RentCycle {
        if (!$rentCycle->isActive()) {
            throw ValidationException::withMessages([
                'rent' => 'Rent cycle is already ended.',
            ]);
        }

        $endDate = $endDate
            ? Carbon::parse($endDate)
            : now();

        // LOGICAL SAFETY
        if ($endDate->lt($rentCycle->start_date)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date cannot be before start date.',
            ]);
        }

        $rentCycle->update([
            'end_date' => $endDate,
            'note' => $note
                ? trim(($rentCycle->note ?? '') . "\nEnd: " . $note)
                : $rentCycle->note,
        ]);

        return $rentCycle;
    }
}
