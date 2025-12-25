<?php

namespace App\Services;

use App\Models\RentBilling;
use App\Models\RentCycle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentPaymentService
{
    /**
     * Apply a payment to unpaid rent billings (FIFO).
     *
     * IMPORTANT DOMAIN RULES:
     * - This service DOES NOT create ledger transactions
     * - Ledger entries are created via RentBillingPaid event
     * - Safe to call from:
     *   - Tenant page
     *   - Admin dashboard
     *   - Backfill / migration
     */
    public function applyPayment(
        RentCycle $rent,
        int $amount,
        string $paidAt
    ): void {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        DB::transaction(function () use ($rent, $amount, $paidAt) {

            $paidAt    = Carbon::parse($paidAt);
            $remaining = $amount;

            /*
            |--------------------------------------------------
            | Load unpaid billings (FIFO by billing_month)
            |--------------------------------------------------
            */
            $billings = RentBilling::query()
                ->where('rent_cycle_id', $rent->id)
                ->whereNull('paid_at')
                ->orderBy('billing_month')
                ->lockForUpdate()
                ->get();

            /*
            |--------------------------------------------------
            | VALID CASE:
            | - No unpaid billings (advance / manual payment)
            |--------------------------------------------------
            */
            if ($billings->isEmpty()) {
                return;
            }

            /*
            |--------------------------------------------------
            | Apply payment sequentially
            |--------------------------------------------------
            */
            foreach ($billings as $billing) {
                if ($remaining <= 0) {
                    break;
                }

                if ($remaining >= $billing->amount) {
                    // Full payment for this billing
                    $billing->markPaid($paidAt); // <-- EVENT FIRED HERE
                    $remaining -= $billing->amount;
                } else {
                    // Partial payment not supported (by design)
                    break;
                }
            }
        });
    }
}
