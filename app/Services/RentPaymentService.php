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
     * DOMAIN RULES (IMPORTANT):
     * - This service ONLY settles rent billings
     * - It DOES NOT create ledger transactions
     * - Ledger entries are created via RentBillingPaid event
     * - Payment is applied FIFO by billing_month
     * - Partial payment per billing is NOT supported (by design)
     *
     * Safe to call from:
     * - Tenant detail page
     * - Unpaid rent dashboard
     * - Backfill / migration
     */
    public function applyPayment(
        RentCycle $rent,
        int $amount,
        string $paidAt,
        ?string $note = null
    ): void {
        /* -----------------------------------------
            BASIC VALIDATION
        ------------------------------------------ */
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        DB::transaction(function () use ($rent, $amount, $paidAt, $note) {

            $paidAt    = Carbon::parse($paidAt);
            $remaining = $amount;

            /* -----------------------------------------
                LOAD UNPAID BILLINGS (FIFO)
            ------------------------------------------ */
            $billings = RentBilling::query()
                ->where('rent_cycle_id', $rent->id)
                ->whereNull('paid_at')
                ->orderBy('billing_month')
                ->lockForUpdate()
                ->get();

            /* -----------------------------------------
                VALID CASE:
                - No unpaid billings
                - (advance / manual payment)
            ------------------------------------------ */
            if ($billings->isEmpty()) {
                return;
            }

            /* -----------------------------------------
                APPLY PAYMENT SEQUENTIALLY
            ------------------------------------------ */
            foreach ($billings as $billing) {
                if ($remaining <= 0) {
                    break;
                }

                // Only full billing payments are allowed
                if ($remaining >= $billing->amount) {
                    $billing->markPaid(
                        paidAt: $paidAt,
                        note: $note 
                    );

                    $remaining -= $billing->amount;
                } else {
                    // Partial payment not supported (intentional)
                    break;
                }
            }
        });
    }
}
