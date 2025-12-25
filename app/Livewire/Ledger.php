<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionTag;
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

class Ledger extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /* ======================================================
        FILTER STATE (REALTIME)
    ======================================================= */
    public ?string $month = null;        // YYYY-MM
    public ?int $filterUnitId = null;
    public ?int $filterTenantId = null;

    /* ======================================================
        ADD TRANSACTION MODAL
    ======================================================= */
    public bool $showTransactionModal = false;

    public string $txType = 'expense';
    public string $txCategory = '';
    public int    $txAmount = 0;
    public string $txDate;
    public string $txNote = '';

    // DOMAIN SCOPE
    public string $txScope = 'global'; // global | unit | tenant
    public ?int $txUnitId = null;
    public ?int $txTenantId = null;

    /* ======================================================
        CORRECTION MODAL
    ======================================================= */
    public bool $showCorrectionModal = false;
    public ?Transaction $correctionTarget = null;
    public ?int $correctionTargetId = null;
    public int $correctionAmount = 0;
    public string $correctionNote = '';

    /* ======================================================
        LIFECYCLE
    ======================================================= */
    public function mount(): void
    {
        $this->month  = null;
        $this->txDate = now()->toDateString();
    }

    /* ======================================================
        RESET PAGINATION ON FILTER CHANGE
    ======================================================= */
    public function updatedMonth()          { $this->resetPage(); }
    public function updatedFilterUnitId()   { $this->resetPage(); }
    public function updatedFilterTenantId() { $this->resetPage(); }

    /* ======================================================
        MASTER DATA
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
        FILTER UTIL
    ======================================================= */
    public function clearFilters(): void
    {
        $this->month = null;
        $this->filterUnitId = null;
        $this->filterTenantId = null;
        $this->resetPage();
    }

    /* ======================================================
        ADD TRANSACTION
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
        $this->txType     = 'expense';
        $this->txCategory = '';
        $this->txAmount   = 0;
        $this->txDate     = now()->toDateString();
        $this->txNote     = '';

        $this->txScope    = 'global';
        $this->txUnitId   = null;
        $this->txTenantId = null;
    }

    public function saveTransaction(): void
    {
        $this->validate([
            'txType'     => 'required|in:income,expense',
            'txCategory' => 'required|string|max:50',
            'txAmount'   => 'required|integer|min:1',
            'txDate'     => 'required|date',
            'txNote'     => 'nullable|string|max:255',
            'txScope'    => 'required|in:global,unit,tenant',
            'txUnitId'   => 'nullable|exists:units,id',
            'txTenantId' => 'nullable|exists:tenants,id',
        ]);

        if ($this->txScope === 'unit' && ! $this->txUnitId) {
            throw ValidationException::withMessages([
                'txUnitId' => 'Unit is required.',
            ]);
        }

        if ($this->txScope === 'tenant' && ! $this->txTenantId) {
            throw ValidationException::withMessages([
                'txTenantId' => 'Tenant is required.',
            ]);
        }

        DB::transaction(function () {

            $tx = Transaction::create([
                'type'          => $this->txType,
                'category'      => $this->txCategory,
                'amount'        => $this->txAmount,
                'transacted_at' => $this->txDate,
                'note'          => $this->txNote,
            ]);

            if ($this->txScope === 'unit') {
                TransactionTag::create([
                    'transaction_id' => $tx->id,
                    'type'           => 'unit',
                    'reference_id'   => $this->txUnitId,
                ]);
            }

            if ($this->txScope === 'tenant') {
                TransactionTag::create([
                    'transaction_id' => $tx->id,
                    'type'           => 'tenant',
                    'reference_id'   => $this->txTenantId,
                ]);
            }
        });

        $this->closeTransactionModal();
        $this->resetPage();
    }

    /* ======================================================
        CORRECTION FLOW
    ======================================================= */
    public function openCorrection(int $transactionId): void
    {
        $this->correctionTarget   = Transaction::findOrFail($transactionId);
        $this->correctionTargetId = $transactionId;
        $this->showCorrectionModal = true;
    }

    public function closeCorrection(): void
    {
        $this->showCorrectionModal = false;
        $this->correctionTarget   = null;
        $this->correctionTargetId = null;
        $this->correctionAmount   = 0;
        $this->correctionNote     = '';
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
        RENDER — INI KUNCI
    ======================================================= */
    public function render()
    {
        $query = Transaction::query()
            ->with(['original', 'tags'])
            ->withCount('corrections')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id');

        // FILTER: MONTH
        if ($this->month && preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $start = "{$this->month}-01 00:00:00";
            $end   = date('Y-m-t 23:59:59', strtotime($start));
            $query->whereBetween('transacted_at', [$start, $end]);
        }

        // FILTER: UNIT
        if ($this->filterUnitId) {
            $query->whereHas('tags', fn ($q) =>
                $q->where('type', 'unit')
                  ->where('reference_id', $this->filterUnitId)
            );
        }

        // FILTER: TENANT
        if ($this->filterTenantId) {
            $query->whereHas('tags', fn ($q) =>
                $q->where('type', 'tenant')
                  ->where('reference_id', $this->filterTenantId)
            );
        }

        return view('livewire.ledger', [
            'transactions' => $query->paginate(25),
        ])->layout('layouts.dashboard');
    }
}
