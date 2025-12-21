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
        string $note
    ): Transaction {
        if ($original->isCorrection()) {
            throw new InvalidArgumentException('Cannot correct a correction.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Correction amount must be positive.');
        }

        if (trim($note) === '') {
            throw new InvalidArgumentException('Correction note is required.');
        }

        return Transaction::create([
            'type' => $original->type === 'income' ? 'expense' : 'income',
            'amount' => $amount,
            'category' => 'Correction',
            'note' => $note,
            'transacted_at' => Carbon::now()->toDateString(),
            'correction_of' => $original->id,
        ]);
    }
}
