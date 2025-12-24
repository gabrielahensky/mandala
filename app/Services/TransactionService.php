<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TransactionService
{
    public function correct(
        Transaction $original,
        int $amount,
        string $note,
        ?Carbon $transactedAt = null
    ): Transaction {
        // =====================
        // Guards
        // =====================

        if ($original->isCorrection()) {
            throw new InvalidArgumentException(
                'Cannot correct a correction transaction.'
            );
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Correction amount must be positive.'
            );
        }

        if (trim($note) === '') {
            throw new InvalidArgumentException(
                'Correction note is required.'
            );
        }

        // =====================
        // Determine opposite type
        // =====================

        $type = $original->type === 'income'
            ? 'expense'
            : 'income';

        // =====================
        // Create correction
        // =====================

        return Transaction::create([
            'type'            => $type,
            'amount'          => $amount,
            'category'        => $original->category, // IMPORTANT
            'note'            => '[Correction] ' . $note,
            'transacted_at'   => ($transactedAt ?? now())->toDateString(),

            // Audit trail
            'correction_of'   => $original->id,

            // Domain inheritance
            'rent_cycle_id'   => $original->rent_cycle_id,
        ]);
    }
}
