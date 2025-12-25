<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RentBilling;
use App\Models\Transaction;
use App\Models\TransactionTag;

class BackfillRentLedgerSource extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:backfill-ledger-source';

    /**
     * The console command description.
     */
    protected $description = 'Backfill source_type/source_id for legacy rent ledger transactions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning rent billings...');

        $billings = RentBilling::query()
            ->whereNotNull('paid_at')
            ->with('rentCycle')
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($billings as $billing) {

            // SAFETY: rentCycle wajib ada
            if (! $billing->rentCycle) {
                $this->warn("Billing #{$billing->id} has no rentCycle. Skipped.");
                $skipped++;
                continue;
            }

            // SAFETY: kalau sudah punya source → skip
            if (
                Transaction::fromSource('rent_billing', $billing->id)->exists()
            ) {
                $skipped++;
                continue;
            }

            // Cari transaksi lama (heuristik konservatif)
            $tx = Transaction::query()
                ->where('category', 'rent')
                ->where('amount', $billing->amount)
                ->whereDate('transacted_at', $billing->paid_at)
                ->whereNull('source_type')
                ->orderBy('id')
                ->first();

            if (! $tx) {
                $this->warn("No matching ledger for billing #{$billing->id}");
                $skipped++;
                continue;
            }

            // Isi source (SINGLE SOURCE OF TRUTH)
            $tx->update([
                'source_type' => 'rent_billing',
                'source_id'   => $billing->id,
            ]);

            // Pastikan tag tenant
            TransactionTag::firstOrCreate([
                'transaction_id' => $tx->id,
                'type'           => 'tenant',
                'reference_id'   => $billing->rentCycle->tenant_id,
            ]);

            // Pastikan tag unit
            TransactionTag::firstOrCreate([
                'transaction_id' => $tx->id,
                'type'           => 'unit',
                'reference_id'   => $billing->rentCycle->unit_id,
            ]);

            $updated++;
        }

        $this->info("Backfill finished.");
        $this->info("Updated : {$updated}");
        $this->info("Skipped : {$skipped}");

        return Command::SUCCESS;
    }
}
