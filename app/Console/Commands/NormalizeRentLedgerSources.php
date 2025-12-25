<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;

class NormalizeRentLedgerSources extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:normalize-ledger-sources';

    /**
     * The console command description.
     */
    protected $description = 'Normalize legacy rent ledger transactions by assigning source_type/source_id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning legacy rent ledger transactions...');

        $query = Transaction::query()
            ->where('category', 'rent')
            ->whereNull('source_type');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No legacy rent transactions found. Ledger already clean.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} legacy rent transactions.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        $query->orderBy('id')->chunkById(100, function ($transactions) use (&$updated, $bar) {
            foreach ($transactions as $tx) {
                $tx->update([
                    'source_type' => 'rent_legacy',
                    'source_id'   => $tx->id,
                ]);

                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Normalization complete. {$updated} transactions updated.");

        return Command::SUCCESS;
    }
}
