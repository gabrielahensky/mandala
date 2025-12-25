<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionTag;
use Illuminate\Support\Facades\DB;

class BackfillRentTransactionTags extends Command
{
    protected $signature = 'rent:backfill-ledger-tags';
    protected $description = 'Auto-tag existing rent payment transactions (tenant, unit, rent_cycle)';

    public function handle(): int
    {
        $this->info('Scanning rent transactions...');

        $transactions = Transaction::query()
            ->where('category', 'Rent')
            ->whereDoesntHave('tags') // 🔥 penting: idempotent
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions need tagging.');
            return self::SUCCESS;
        }

        $count = 0;

        DB::transaction(function () use ($transactions, &$count) {
            foreach ($transactions as $tx) {

                // CASE 1: ada rent_cycle_id (ideal)
                if ($tx->rent_cycle_id) {
                    $rent = $tx->rentCycle;

                    if (! $rent) continue;

                    TransactionTag::insert([
                        [
                            'transaction_id' => $tx->id,
                            'type' => 'tenant',
                            'reference_id' => $rent->tenant_id,
                        ],
                        [
                            'transaction_id' => $tx->id,
                            'type' => 'unit',
                            'reference_id' => $rent->unit_id,
                        ],
                        [
                            'transaction_id' => $tx->id,
                            'type' => 'rent_cycle',
                            'reference_id' => $rent->id,
                        ],
                    ]);

                    $count++;
                    continue;
                }

                // CASE 2: fallback (legacy data)
                $tags = [];

                if ($tx->tenant_id) {
                    $tags[] = [
                        'transaction_id' => $tx->id,
                        'type' => 'tenant',
                        'reference_id' => $tx->tenant_id,
                    ];
                }

                if ($tx->unit_id) {
                    $tags[] = [
                        'transaction_id' => $tx->id,
                        'type' => 'unit',
                        'reference_id' => $tx->unit_id,
                    ];
                }

                if (! empty($tags)) {
                    TransactionTag::insert($tags);
                    $count++;
                }
            }
        });

        $this->info("Tagged {$count} rent transactions.");

        return self::SUCCESS;
    }
}
