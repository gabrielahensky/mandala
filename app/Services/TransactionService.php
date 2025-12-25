<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;

class TransactionService
{
    public function recordIncome(array $data): Transaction
    {
        return Transaction::create([
            'type'          => 'income',
            'category'      => $data['category'],
            'amount'        => $data['amount'],
            'transacted_at' => Carbon::parse($data['date']),
            'unit_id'       => $data['unit_id'] ?? null,
            'tenant_id'     => $data['tenant_id'] ?? null,
            'note'          => $data['note'] ?? null,
            'rent_cycle_id' => $data['rent_cycle_id'] ?? null,
        ]);
    }

    public function recordExpense(array $data): Transaction
    {
        return Transaction::create([
            'type'          => 'expense',
            'category'      => $data['category'],
            'amount'        => $data['amount'],
            'transacted_at' => Carbon::parse($data['date']),
            'unit_id'       => $data['unit_id'] ?? null,
            'tenant_id'     => $data['tenant_id'] ?? null,
            'note'          => $data['note'] ?? null,
        ]);
    }
}
