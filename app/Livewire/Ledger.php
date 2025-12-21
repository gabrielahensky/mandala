<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;

class Ledger extends Component
{
    public ?int $filterUnitId = null;
    public ?int $filterTenantId = null;

    // ===== Filter State =====
    public string $month;

    // ===== Data =====
    public $transactions;

    // ===== Correction Modal State =====
    public bool $showCorrectionModal = false;
    public ?int $correctionTargetId = null;
    public ?Transaction $correctionTarget = null;
    public int $correctionAmount = 0;
    public string $correctionNote = '';

    public function getUnitsProperty()
    {
        return \App\Models\Unit::orderBy('name')->get();
    }

    public function getTenantsProperty()
    {
        return \App\Models\Tenant::orderBy('name')->get();
    }

    /* =========================
        Lifecycle
    ========================== */

    public function mount()
    {
        $this->month = now()->format('Y-m');
        $this->loadTransactions();
    }

    /* =========================
        Month Filter
    ========================== */

    public function applyMonth()
    {
        $this->loadTransactions();
    }

    /* =========================
        Correction Flow
    ========================== */

    public function openCorrection(int $transactionId)
    {
        $this->resetCorrectionForm();

        $this->correctionTarget = Transaction::findOrFail($transactionId);
        $this->correctionTargetId = $transactionId;
        $this->showCorrectionModal = true;
    }

    public function closeCorrection()
    {
        $this->showCorrectionModal = false;
        $this->resetCorrectionForm();
    }

    protected function resetCorrectionForm()
    {
        $this->correctionTarget = null;
        $this->correctionTargetId = null;
        $this->correctionAmount = 0;
        $this->correctionNote = '';
    }

    public function submitCorrection(TransactionService $service)
    {
        $this->validate([
            'correctionTargetId' => 'required|integer|exists:transactions,id',
            'correctionAmount' => 'required|integer|min:1',
            'correctionNote' => 'required|string|min:5',
        ]);

        $original = Transaction::findOrFail($this->correctionTargetId);

        $service->correct(
            $original,
            $this->correctionAmount,
            $this->correctionNote
        );

        // reload ledger after correction
        $this->loadTransactions();

        $this->closeCorrection();
    }

    /* =========================
        Core Query
    ========================== */

    protected function loadTransactions()
    {
        $date = Carbon::createFromFormat('Y-m', $this->month);
    
        $query = Transaction::query()
            ->with(['tags', 'evidences', 'original'])
            ->withCount(['corrections', 'evidences'])
            ->whereBetween('transacted_at', [
                $date->startOfMonth()->toDateString(),
                $date->endOfMonth()->toDateString(),
            ])
            ->orderBy('transacted_at');
    
        if ($this->filterUnitId) {
            $query->forUnit($this->filterUnitId);
        }
    
        if ($this->filterTenantId) {
            $query->forTenant($this->filterTenantId);
        }
    
        $this->transactions = $query->get();
    }    

    public function refreshLedger()
    {
        $this->loadTransactions();
    }

    /* =========================
        Render
    ========================== */

    public function render()
    {
        return view('livewire.ledger')
            ->layout('layouts.dashboard');
    }
}
