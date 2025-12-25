<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionTag;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class BackfillExpenseTags extends Command
{
    protected $signature = 'ledger:backfill-expense-tags 
        {--dry-run : Only show what would be updated}';

    protected $description = 'Backfill unit/tenant tags for expense transactions (safe & idempotent)';

    public function handle(): int
    {
        $this->info('🔍 Scanning expense transactions without tags...');

        $dryRun = $this->option('dry-run');

        $expenses = Transaction::query()
            ->where('type', 'expense')
            ->whereDoesntHave('tags')
            ->orderBy('id')
            ->get();

        if ($expenses->isEmpty()) {
            $this->info('✅ No untagged expense transactions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$expenses->count()} transactions.");

        $updated = 0;
        $skipped = 0;

        foreach ($expenses as $tx) {
            $matchedUnit = $this->detectUnitFromNoteOrCategory($tx);

            if (! $matchedUnit) {
                $skipped++;
                $this->line("⏭️  Skip TX #{$tx->id} (no unit detected)");
                continue;
            }

            if ($dryRun) {
                $this->line("🧪 Would tag TX #{$tx->id} → unit {$matchedUnit->name}");
                $updated++;
                continue;
            }

            DB::transaction(function () use ($tx, $matchedUnit) {
                TransactionTag::firstOrCreate([
                    'transaction_id' => $tx->id,
                    'type'           => 'unit',
                    'reference_id'   => $matchedUnit->id,
                ]);
            });

            $updated++;
            $this->line("✅ Tagged TX #{$tx->id} → unit {$matchedUnit->name}");
        }

        $this->newLine();
        $this->info('🎯 Backfill finished.');
        $this->info("Updated : {$updated}");
        $this->info("Skipped : {$skipped}");

        return self::SUCCESS;
    }

    /**
     * VERY EXPLICIT rule:
     * - cari nama unit di category / note
     * - contoh: "kamar A1", "Unit B2", "renov kamar C3"
     */
    protected function detectUnitFromNoteOrCategory(Transaction $tx): ?Unit
    {
        $haystack = strtolower(
            trim(($tx->category ?? '') . ' ' . ($tx->note ?? ''))
        );

        if ($haystack === '') {
            return null;
        }

        foreach (Unit::all() as $unit) {
            $name = strtolower($unit->name);

            if (str_contains($haystack, $name)) {
                return $unit;
            }
        }

        return null;
    }
}
