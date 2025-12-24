<?php

namespace App\Services;

use App\Models\RentBilling;
use App\Models\RentCycle;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentPaymentService
{
    /**
     * Apply payment to rent invoices (oldest unpaid first).
     *
     * NOTE:
     * - Supports multi-invoice settlement
     * - Partial invoice payment is NOT persisted (yet)
     */
    public function applyPayment(
        RentCycle $rent,
        int $amount,
        Carbon|string $paidAt,
        ?string $note = null
    ): void {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $paidAt = Carbon::parse($paidAt);

        DB::transaction(function () use ($rent, $amount, $paidAt, $note) {

            $remaining = $amount;

            // lock unpaid invoices (oldest first)
            $invoices = RentBilling::query()
                ->where('rent_cycle_id', $rent->id)
                ->whereNull('paid_at')
                ->orderBy('billing_month')
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'No unpaid invoices found for this rent.',
                ]);
            }

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $invoiceAmount = (int) $invoice->amount;

                if ($remaining >= $invoiceAmount) {
                    // fully settle invoice
                    $invoice->markPaid($paidAt);
                    $remaining -= $invoiceAmount;
                } else {
                    // partial payment (not persisted yet)
                    // remaining tracked only in ledger difference
                    $remaining = 0;
                }
            }

            // record ledger transaction (single source of truth)
            Transaction::create([
                'type'          => 'income',
                'category'      => 'Rent',
                'amount'        => $amount,
                'note'          => $note,
                'transacted_at' => $paidAt,
                'rent_cycle_id' => $rent->id,
            ]);
        });
    }
}
