<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Unit;
use App\Models\RentCycle;
use App\Models\Transaction;
use App\Models\RentBilling;
use Carbon\Carbon;

class Dashboard extends Component
{
    /* =========================
        Dashboard State
    ========================== */
    public array $summary = [];
    public array $transactions = [];
    public array $incomeByCategory = [];
    public array $expenseByCategory = [];

    /* =========================
        Unit Occupancy
    ========================== */
    public array $unitStats = [
        'occupied' => 0,
        'available' => 0,
        'inactive' => 0,
        'total_active' => 0,
    ];

    /* =========================
        Rent Reminder
    ========================== */
    public array $rentAlerts = [];
    public int $overdueCount = 0;

    /* =========================
        Form State
    ========================== */
    public string $type = 'income';
    public string $category = '';
    public int $amount = 0;
    public string $transacted_at = '';
    public ?string $note = null;

    /* =========================
        Date Range (FOR SUMMARY ONLY)
    ========================== */
    public string $range = 'this_month';

    /* =========================
        Lifecycle
    ========================== */
    public function mount(): void
    {
        $this->transacted_at = now()->toDateString();
        $this->loadData();
    }

    /* =========================
        Actions
    ========================== */
    public function changeRange(string $range): void
    {
        $this->range = $range;
        $this->loadData();
    }

    public function addTransaction(): void
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
            'transacted_at' => Carbon::parse($this->transacted_at),
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

    protected function loadData(): void
    {
        [$start, $end] = $this->getDateRange();

        /* =========================
            SUMMARY (RANGE-BASED)
        ========================== */
        $summaryQuery = Transaction::query()
            ->whereBetween('transacted_at', [$start, $end]);

        $income = (clone $summaryQuery)->where('type', 'income')->sum('amount');
        $expense = (clone $summaryQuery)->where('type', 'expense')->sum('amount');

        $this->summary = [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];

        /* =========================
            RECENT ACTIVITY (GLOBAL)
        ========================== */
        $this->transactions = Transaction::query()
            ->orderByDesc('transacted_at')
            ->limit(7)
            ->get()
            ->map(fn (Transaction $t) => [
                'date' => $t->transacted_at->diffForHumans(),
                'type' => $t->type,
                'category' => $t->category === 'Rent'
                    ? 'Rent Payment'
                    : $t->category,
                'amount' => $t->amount,
                'note' => $t->note,
            ])
            ->toArray();

        /* =========================
            CATEGORY BREAKDOWN (RANGE)
        ========================== */
        $this->incomeByCategory = (clone $summaryQuery)
            ->where('type', 'income')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        $this->expenseByCategory = (clone $summaryQuery)
            ->where('type', 'expense')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        /* =========================
            UNIT OCCUPANCY (SOURCE OF TRUTH)
        ========================== */
        $totalActiveUnits = Unit::where('is_active', true)->count();

        $occupiedUnits = RentCycle::query()
            ->whereNull('end_date')
            ->distinct('unit_id')
            ->count('unit_id');

        $this->unitStats = [
            'occupied' => $occupiedUnits,
            'available' => max(0, $totalActiveUnits - $occupiedUnits),
            'inactive' => Unit::where('is_active', false)->count(),
            'total_active' => $totalActiveUnits,
        ];

        /* =========================
            RENT ALERTS (GLOBAL)
        ========================== */
        $billings = RentBilling::query()
            ->whereNull('paid_at')
            ->with([
                'rentCycle.tenant' => fn ($q) => $q->withTrashed(),
                'rentCycle.unit' => fn ($q) => $q->withTrashed(),
            ])
            ->orderBy('due_date')
            ->get();

        $this->rentAlerts = $billings
            ->take(5)
            ->map(fn (RentBilling $bill) => [
                'tenant' => optional($bill->rentCycle->tenant)->name ?? '[Deleted Tenant]',
                'unit' => optional($bill->rentCycle->unit)->name ?? '[Deleted Unit]',
                'amount' => $bill->amount,
                'due_date' => $bill->due_date,
                'label' => $bill->reminderLabel(),
            ])
            ->toArray();

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
