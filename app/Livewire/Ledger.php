<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

class Ledger extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /* ======================================================
        FILTER STATE
    ======================================================= */
    public ?string $month = null; // YYYY-MM
    public ?int $filterUnitId = null;
    public ?int $filterTenantId = null;

    /* ======================================================
        ADD TRANSACTION MODAL
    ======================================================= */
    public bool $showTransactionModal = false;

    public string $txType = 'expense'; // income | expense
    public string $txCategory = '';
    public int $txAmount = 0;
    public string $txDate;

    public string $txScope = 'global'; // global | unit | tenant
    public ?int $txUnitId = null;
    public ?int $txTenantId = null;

    public string $txNote = '';

    /* ======================================================
        CORRECTION MODAL
    ======================================================= */
    public bool $showCorrectionModal = false;
    public ?int $correctionTargetId = null;
    public ?Transaction $correctionTarget = null;
    public int $correctionAmount = 0;
    public string $correctionNote = '';

    /* ======================================================
        LIFECYCLE
    ======================================================= */
    public function mount(): void
    {
        // default: all-time ledger
        $this->month = null;
        $this->txDate = now()->toDateString();
    }

    /* ======================================================
        RESET PAGINATION ON FILTER CHANGE
    ======================================================= */
    public function updatedMonth() { $this->resetPage(); }
    public function updatedFilterUnitId() { $this->resetPage(); }
    public function updatedFilterTenantId() { $this->resetPage(); }

    /* ======================================================
        COMPUTED: UNITS & TENANTS
    ======================================================= */
    public function getUnitsProperty()
    {
        return \App\Models\Unit::orderBy('name')->get();
    }

    public function getTenantsProperty()
    {
        return \App\Models\Tenant::orderBy('name')->get();
    }

    /* ======================================================
        BASE QUERY (SINGLE SOURCE OF TRUTH)
    ======================================================= */
    protected function baseQuery()
    {
        $query = Transaction::query()
            ->with(['original'])
            ->withCount(['corrections'])
            ->orderByDesc('transacted_at')
            ->orderByDesc('id');

        // MONTH FILTER (SAFE STRING BASED)
        if ($this->month && preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $start = $this->month . '-01 00:00:00';
            $end   = date('Y-m-t 23:59:59', strtotime($start));
            $query->whereBetween('transacted_at', [$start, $end]);
        }

        if ($this->filterUnitId) {
            $query->forUnit($this->filterUnitId);
        }

        if ($this->filterTenantId) {
            $query->forTenant($this->filterTenantId);
        }

        return $query;
    }

    public function getTransactionsProperty()
    {
        return $this->baseQuery()->paginate(25);
    }

    public function clearFilters(): void
    {
        $this->month = null;
        $this->filterUnitId = null;
        $this->filterTenantId = null;
        $this->resetPage();
    }

    /* ======================================================
        ADD TRANSACTION FLOW
    ======================================================= */
    public function openTransactionModal(): void
    {
        $this->resetTransactionForm();
        $this->showTransactionModal = true;
    }

    public function closeTransactionModal(): void
    {
        $this->showTransactionModal = false;
        $this->resetTransactionForm();
    }

    protected function resetTransactionForm(): void
    {
        $this->txType = 'expense';
        $this->txCategory = '';
        $this->txAmount = 0;
        $this->txDate = now()->toDateString();
        $this->txScope = 'global';
        $this->txUnitId = null;
        $this->txTenantId = null;
        $this->txNote = '';
    }

    public function saveTransaction(): void
    {
        $this->validate([
            'txType'     => 'required|in:income,expense',
            'txCategory' => 'required|string|max:50',
            'txAmount'   => 'required|integer|min:1',
            'txDate'     => 'required|date',
            'txScope'    => 'required|in:global,unit,tenant',
            'txUnitId'   => 'nullable|exists:units,id',
            'txTenantId' => 'nullable|exists:tenants,id',
            'txNote'     => 'nullable|string|max:255',
        ]);

        // DOMAIN GUARDS
        if ($this->txScope === 'unit' && ! $this->txUnitId) {
            throw ValidationException::withMessages([
                'txUnitId' => 'Unit is required for unit-scoped transaction.',
            ]);
        }

        if ($this->txScope === 'tenant' && ! $this->txTenantId) {
            throw ValidationException::withMessages([
                'txTenantId' => 'Tenant is required for tenant-scoped transaction.',
            ]);
        }

        Transaction::create([
            'type'          => $this->txType,
            'category'      => $this->txCategory,
            'amount'        => $this->txAmount,
            'transacted_at' => $this->txDate,
            'unit_id'       => $this->txScope !== 'global'
                ? $this->txUnitId
                : null,
            'tenant_id'     => $this->txScope === 'tenant'
                ? $this->txTenantId
                : null,
            'note'          => $this->txNote,
        ]);

        $this->closeTransactionModal();
        $this->resetPage();
    }

    /* ======================================================
        CORRECTION FLOW
    ======================================================= */
    public function openCorrection(int $transactionId): void
    {
        $this->resetCorrectionForm();
        $this->correctionTarget = Transaction::findOrFail($transactionId);
        $this->correctionTargetId = $transactionId;
        $this->showCorrectionModal = true;
    }

    public function closeCorrection(): void
    {
        $this->showCorrectionModal = false;
        $this->resetCorrectionForm();
    }

    protected function resetCorrectionForm(): void
    {
        $this->correctionTarget = null;
        $this->correctionTargetId = null;
        $this->correctionAmount = 0;
        $this->correctionNote = '';
    }

    public function submitCorrection(TransactionService $service): void
    {
        $this->validate([
            'correctionTargetId' => 'required|exists:transactions,id',
            'correctionAmount'   => 'required|integer|min:1',
            'correctionNote'     => 'required|string|min:5',
        ]);

        $service->correct(
            Transaction::findOrFail($this->correctionTargetId),
            $this->correctionAmount,
            $this->correctionNote
        );

        $this->closeCorrection();
    }

    /* ======================================================
        RENDER
    ======================================================= */
    public function render()
    {
        return view('livewire.ledger', [
            'transactions' => $this->transactions,
        ])->layout('layouts.dashboard');
    }
}
