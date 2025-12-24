<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\RentBilling;
use Carbon\Carbon;

class Dashboard extends Component
{
    // ===== Dashboard State =====
    public array $summary = [];
    public array $transactions = [];
    public array $incomeByCategory = [];
    public array $expenseByCategory = [];

    // ===== Rent Reminder State =====
    public array $rentAlerts = [];
    public int $overdueCount = 0;

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

        Transaction::create([
            'type' => $this->type,
            'category' => $this->category,
            'amount' => $this->amount,
            'transacted_at' => $this->transacted_at,
            'note' => $this->note,
        ]);

        $this->reset(['category', 'amount', 'note']);
        $this->loadData();
    }

    /* =========================
       Core Logic
    ========================== */

    protected function getDateRange(): array
    {
        if ($this->range === 'last_month') {
            return [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ];
        }

        return [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ];
    }

    protected function loadData()
    {
        [$start, $end] = $this->getDateRange();

        /* ===== TRANSACTIONS BASE ===== */
        $baseQuery = Transaction::query()
            ->whereBetween('transacted_at', [$start, $end]);

        /* ===== SUMMARY ===== */
        $income = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $expense = (clone $baseQuery)->where('type', 'expense')->sum('amount');

        $this->summary = [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];

        /* ===== RECENT TRANSACTIONS ===== */
        $this->transactions = (clone $baseQuery)
            ->orderByDesc('transacted_at')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'date' => $t->transacted_at->format('Y-m-d'),
                'type' => $t->type,
                'category' => $t->category,
                'amount' => $t->amount,
                'note' => $t->note,
            ])
            ->toArray();

        /* ===== CATEGORY BREAKDOWN ===== */
        $this->incomeByCategory = (clone $baseQuery)
            ->where('type', 'income')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $this->expenseByCategory = (clone $baseQuery)
            ->where('type', 'expense')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        /* ===== RENT BILLING ALERTS (INVOICE-BASED) ===== */
        $billings = RentBilling::query()
            ->whereNull('paid_at')
            ->with(['rentCycle.tenant', 'rentCycle.unit'])
            ->orderBy('due_date')
            ->get();

        $this->rentAlerts = $billings->map(function ($bill) {
            return [
                'tenant' => $bill->rentCycle->tenant->name,
                'unit' => $bill->rentCycle->unit->name,
                'amount' => $bill->amount,
                'due_date' => $bill->due_date,
                'label' => $bill->reminderLabel(), // H-7, H-6, ..., H, OVERDUE
            ];
        })->toArray();

        $this->overdueCount = $billings
            ->filter(fn ($b) => $b->reminderLabel() === 'OVERDUE')
            ->count();
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('layouts.dashboard');
    }
}
