<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\RentBilling;
use App\Services\RentCycleService;
use App\Services\RentBillingService;
use App\Services\RentPaymentService;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TenantDetail extends Component
{
    /* =====================================================
        CORE ENTITY
    ====================================================== */
    public Tenant $tenant;

    /* =====================================================
        DOMAIN STATE (READ-ONLY FOR UI)
    ====================================================== */
    public $activeRent = null;
    public Collection $activeInvoices;
    public Collection $rentTransactions;
    public ?array $tenantBillingSummary = null;

    /* =====================================================
        UI STATE
    ====================================================== */
    public bool $showAssignUnitModal = false;
    public bool $showEndRentModal    = false;
    public bool $showPaymentModal   = false;

    /* =====================================================
        ASSIGN UNIT FORM
    ====================================================== */
    public ?int $selectedUnitId = null;
    public string $startDate = '';

    /* =====================================================
        END RENT FORM
    ====================================================== */
    public string $endDate = '';
    public string $endNote = '';

    /* =====================================================
        PAYMENT FORM
    ====================================================== */
    public int $amount = 0;
    public string $paidAt = '';
    public string $note = '';

    /* =====================================================
        LIFECYCLE
    ====================================================== */
    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;

        $today = now()->toDateString();
        $this->startDate = $today;
        $this->endDate   = $today;
        $this->paidAt    = $today;

        $this->reloadData();
    }

    /* =====================================================
        CORE RELOAD (SINGLE SOURCE OF TRUTH)
    ====================================================== */
    protected function reloadData(): void
    {
        $this->tenant->refresh();

        $this->activeRent = $this->tenant
            ->rentCycles()
            ->whereNull('end_date')
            ->with('unit')
            ->first();

        // defaults (WAJIB)
        $this->activeInvoices = collect();
        $this->rentTransactions = collect();
        $this->tenantBillingSummary = null;

        if (! $this->activeRent) {
            return;
        }

        $this->activeInvoices = RentBilling::query()
            ->where('rent_cycle_id', $this->activeRent->id)
            ->whereNull('paid_at')
            ->orderBy('billing_month')
            ->get();

        $this->rentTransactions = $this->activeRent
            ->transactions()
            ->where('category', 'Rent')
            ->orderByDesc('transacted_at')
            ->get();

        $this->tenantBillingSummary = app(RentBillingService::class)
            ->summaryWithCarry($this->activeRent, now());
    }

    /* =====================================================
        DERIVED DATA
    ====================================================== */
    public function getAvailableUnitsProperty()
    {
        return Unit::query()
            ->whereDoesntHave('activeRent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /* =====================================================
        ASSIGN UNIT — UI
    ====================================================== */
    public function openAssignUnitModal(): void
    {
        $this->resetErrorBag();

        if ($this->activeRent) {
            $this->addError('rent', 'Tenant already has an active rent.');
            return;
        }

        $this->selectedUnitId = null;
        $this->startDate = now()->toDateString();
        $this->showAssignUnitModal = true;
    }

    public function closeAssignUnitModal(): void
    {
        $this->showAssignUnitModal = false;
        $this->resetErrorBag();
    }

    /* =====================================================
        ASSIGN UNIT — COMMAND
    ====================================================== */
    public function assignUnit(): void
    {
        $this->validate([
            'selectedUnitId' => 'required|exists:units,id',
            'startDate'      => 'required|date',
        ]);

        app(RentCycleService::class)->startRent(
            unit: Unit::findOrFail($this->selectedUnitId),
            tenant: $this->tenant,
            startDate: Carbon::parse($this->startDate)
        );

        $this->closeAssignUnitModal();
        $this->reloadData();
    }

    /* =====================================================
        END RENT — UI
    ====================================================== */
    public function openEndRentModal(): void
    {
        $this->resetErrorBag();

        if (! $this->activeRent) {
            $this->addError('rent', 'Tenant is not currently renting.');
            return;
        }

        $this->endDate = now()->toDateString();
        $this->endNote = '';
        $this->showEndRentModal = true;
    }

    public function closeEndRentModal(): void
    {
        $this->showEndRentModal = false;
        $this->resetErrorBag();
    }

    /* =====================================================
        END RENT — COMMAND
    ====================================================== */
    public function endRent(): void
    {
        app(RentCycleService::class)->endRentSafely(
            rent: $this->activeRent,
            endDate: Carbon::parse($this->endDate),
            note: $this->endNote
        );

        $this->closeEndRentModal();
        $this->reloadData();
    }

    /* =====================================================
        PAYMENT — UI
    ====================================================== */
    public function openPaymentModal(): void
    {
        $this->resetErrorBag();

        if (! $this->activeRent) {
            $this->addError('payment', 'Tenant does not have an active rent.');
            return;
        }

        if (! $this->tenantBillingSummary || $this->tenantBillingSummary['debt'] <= 0) {
            $this->addError('payment', 'No outstanding rent to pay.');
            return;
        }

        $this->amount = $this->tenantBillingSummary['debt'];
        $this->paidAt = now()->toDateString();
        $this->note   = '';

        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
    }

    /* =====================================================
        PAYMENT — COMMAND
    ====================================================== */
    public function savePayment(): void
    {
        $this->validate([
            'amount' => 'required|integer|min:1',
            'paidAt' => 'required|date',
            'note'   => 'nullable|string|max:255',
        ]);

        app(RentPaymentService::class)->applyPayment(
            rent: $this->activeRent,
            amount: $this->amount,
            paidAt: $this->paidAt
        );

        $this->closePaymentModal();
        $this->reloadData();
    }

    /* =====================================================
        RENDER
    ====================================================== */
    public function render()
    {
        return view('livewire.tenant-detail')
            ->layout('layouts.dashboard');
    }
}
