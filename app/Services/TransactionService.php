<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function record(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {

            $tx = Transaction::create([
                'type'          => $data['type'], // income | expense
                'category'      => $data['category'],
                'amount'        => $data['amount'],
                'transacted_at' => $data['date'],
                'note'          => $data['note'] ?? null,
            ]);

            if (!empty($data['unit_id'])) {
                TransactionTag::create([
                    'transaction_id' => $tx->id,
                    'type'           => 'unit',
                    'reference_id'   => $data['unit_id'],
                ]);
            }

            if (!empty($data['tenant_id'])) {
                TransactionTag::create([
                    'transaction_id' => $tx->id,
                    'type'           => 'tenant',
                    'reference_id'   => $data['tenant_id'],
                ]);
            }

            return $tx;
        });
    }

    public function correct(
        Transaction $original,
        int $deltaAmount,
        string $note
    ): Transaction {

        if ($original->isCorrection()) {
            throw ValidationException::withMessages([
                'correction' => 'Cannot correct a correction entry.',
            ]);
        }

        if ($original->corrections()->exists()) {
            throw ValidationException::withMessages([
                'correction' => 'This transaction has already been corrected.',
            ]);
        }

        if ($deltaAmount === 0) {
            throw ValidationException::withMessages([
                'amount' => 'Correction amount must not be zero.',
            ]);
        }

        return DB::transaction(function () use ($original, $deltaAmount, $note) {

            $correction = Transaction::create([
                'type'          => $original->type,
                'category'      => $original->category,
                'amount'        => $deltaAmount, // SIGNED DELTA
                'transacted_at' => now(),
                'note'          => 'Correction: ' . $note,
                'correction_of' => $original->id,
            ]);

            foreach ($original->tags as $tag) {
                TransactionTag::create([
                    'transaction_id' => $correction->id,
                    'type'           => $tag->type,
                    'reference_id'   => $tag->reference_id,
                ]);
            }

            return $correction;
        });
    }
}
