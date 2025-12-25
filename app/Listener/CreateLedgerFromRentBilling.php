<?php

namespace App\Listeners;

use App\Events\RentBillingPaid;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CreateLedgerFromRentBilling
{
    public function handle(RentBillingPaid $event): void
    {
        $billing = $event->billing;
        $rent    = $billing->rentCycle;

        if (! $rent) {
            return;
        }

        // 🔐 Idempotent guard (INI WAJIB)
        if (
            Transaction::fromSource('rent_billing', $billing->id)->exists()
        ) {
            return;
        }

        DB::transaction(function () use ($billing, $rent) {

            $tx = Transaction::create([
                'type'          => 'income',
                'category'      => 'rent',
                'amount'        => $billing->amount,
                'transacted_at' => $billing->paid_at,
                'note'          => 'Rent payment (auto)',
                'source_type'   => 'rent_billing',
                'source_id'     => $billing->id,
            ]);

            $tx->tags()->createMany([
                [
                    'type' => 'tenant',
                    'reference_id' => $rent->tenant_id,
                ],
                [
                    'type' => 'unit',
                    'reference_id' => $rent->unit_id,
                ],
            ]);
        });
    }
}
