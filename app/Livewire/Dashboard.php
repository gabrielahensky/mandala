<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public array $summary = [];
    public array $transactions = [];
    public function mount()
    {
        $income = DB::table('transactions')
        ->where('type', 'income')
        ->sum('amount');

        $expense = DB::table('transactions')
        ->where('type', 'expense')
        ->sum('amount');

        $this->summary = [
        'income' => $income,
        'expense' => $expense,
        'balance' => $income - $expense,
        ];

        $this->transactions = DB::table('transactions')
        ->orderByDesc('transacted_at')
        ->limit(10)
        ->get()
        ->map(function ($t) {
            return [
                'date' => $t->transacted_at,
                'type' => $t->type,
                'category' => $t->category,
                'amount' => $t->amount,
                'note' => $t->note,
            ];
        })
        ->toArray();
    }
    public function render()
    {
        return view('livewire.dashboard')
        ->layout('layouts.app');
    }
}
