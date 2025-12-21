<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Component
{
    // ===== Dashboard State =====
    public array $summary = [];
    public array $transactions = [];
    public array $incomeByCategory = [];
    public array $expenseByCategory = [];

    // ===== Form State =====
    public string $type = 'income';
    public string $category = '';
    public int $amount = 0;
    public string $transacted_at = '';
    public ?string $note = null;

    // ===== Date Range State =====
    public string $range = 'this_month';

    /* =========================
       Lifecycle
    ========================== */

    public function mount()
    {
        // INIT ONCE
        $this->loadData();
    }

    /* =========================
       Public Actions
    ========================== */

    public function changeRange(string $range)
    {
        $this->range = $range;
        $this->loadData();
    }

    public function addTransaction()
    {
        $this->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:50',
            'amount' => 'required|integer|min:1',
            'transacted_at' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        DB::table('transactions')->insert([
            'type' => $this->type,
            'category' => $this->category,
            'amount' => $this->amount,
            'transacted_at' => $this->transacted_at,
            'note' => $this->note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // reset input (ledger = append-only)
        $this->reset(['category', 'amount', 'note']);

        // refresh dashboard
        $this->loadData();
    }

    /* =========================
       Core Logic
    ========================== */

    protected function getDateRange(): array
    {
        if ($this->range === 'last_month') {
            return [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ];
        }

        // default: this month
        return [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ];
    }

    protected function loadData()
    {
        [$start, $end] = $this->getDateRange();

        // ---- SUMMARY ----
        $income = DB::table('transactions')
            ->where('type', 'income')
            ->whereBetween('transacted_at', [$start, $end])
            ->sum('amount');

        $expense = DB::table('transactions')
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [$start, $end])
            ->sum('amount');

        $this->summary = [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];

        // ---- RECENT TRANSACTIONS ----
        $this->transactions = DB::table('transactions')
            ->whereBetween('transacted_at', [$start, $end])
            ->orderByDesc('transacted_at')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'date' => $t->transacted_at,
                'type' => $t->type,
                'category' => $t->category,
                'amount' => $t->amount,
                'note' => $t->note,
            ])
            ->toArray();

        // ---- INCOME BY CATEGORY ----
        $this->incomeByCategory = DB::table('transactions')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->where('type', 'income')
            ->whereBetween('transacted_at', [$start, $end])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'total' => $r->total,
            ])
            ->toArray();

        // ---- EXPENSE BY CATEGORY ----
        $this->expenseByCategory = DB::table('transactions')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [$start, $end])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'total' => $r->total,
            ])
            ->toArray();
    }

    /* =========================
       Render
    ========================== */

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('layouts.app');
    }
}
