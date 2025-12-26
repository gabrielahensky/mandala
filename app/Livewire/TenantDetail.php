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
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TenantDetail extends Component
{
    public Tenant $tenant;

    /* =========================
        DOMAIN STATE
    ========================== */
    public $activeRent = null;
    public Collection $activeInvoices;
    public Collection $rentTransactions;
    public ?array $tenantBillingSummary = null;

    /* =========================
        UI STATE — TENANT
    ========================== */
    public bool $showTenantPaymentModal = false;
    public bool $showTenantAssignUnitModal = false;
    public bool $showTenantEndRentModal = false;

    /* =========================
        PAYMENT FORM
    ========================== */
    public int $amount = 0;
    public string $paidAt;
    public string $note = '';

    /* =========================
        ASSIGN UNIT FORM
    ========================== */
    public ?int $selectedUnitId = null;
    public string $startDate;

    /* =========================
        END RENT FORM
    ========================== */
    public string $endDate;
    public string $endNote = '';

    /* =========================
        LIFECYCLE
    ========================== */
    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;

        $this->paidAt    = now()->toDateString();
        $this->startDate = now()->toDateString();
        $this->endDate   = now()->toDateString();

        $this->reloadData();
    }

    /* =========================
        CORE RELOAD
    ========================== */
    protected function reloadData(): void
    {
        $this->tenant->refresh();

        $this->activeRent = $this->tenant
            ->rentCycles()
            ->whereNull('end_date')
            ->with('unit')
            ->first();

        $this->activeInvoices = $this->activeRent
            ? RentBilling::where('rent_cycle_id', $this->activeRent->id)
                ->whereNull('paid_at')
                ->orderBy('due_date')
                ->get()
            : collect();

        $this->rentTransactions = $this->activeRent
            ? $this->activeRent
                ->transactions()
                ->where('category', 'Rent')
                ->orderByDesc('transacted_at')
                ->get()
            : collect();

        $this->tenantBillingSummary = $this->activeRent
            ? app(RentBillingService::class)
                ->summaryWithCarry($this->activeRent, now())
            : null;
    }

    /* =========================
        DERIVED
    ========================== */
    public function getAvailableUnitsProperty()
    {
        return Unit::whereDoesntHave('activeRent')
            ->orderBy('name')
            ->get();
    }

    /* =========================
        ASSIGN UNIT
    ========================== */
    public function openTenantAssignUnitModal(): void
    {
        if ($this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant already has an active rent.',
            ]);
        }

        $this->selectedUnitId = null;
        $this->startDate = now()->toDateString();
        $this->showTenantAssignUnitModal = true;
    }

    public function closeTenantAssignUnitModal(): void
    {
        $this->showTenantAssignUnitModal = false;
    }

    public function assignUnit(): void
    {
        $this->validate([
            'selectedUnitId' => 'required|exists:units,id',
            'startDate'      => 'required|date',
        ]);

        app(RentCycleService::class)->startRent(
            unit: Unit::findOrFail($this->selectedUnitId),
            tenant: $this->tenant,
            startDate: $this->startDate
        );

        $this->closeTenantAssignUnitModal();
        $this->reloadData();
    }

    /* =========================
        END RENT
    ========================== */
    public function openTenantEndRentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant is not currently renting.',
            ]);
        }

        $this->endDate = now()->toDateString();
        $this->endNote = '';
        $this->showTenantEndRentModal = true;
    }

    public function closeTenantEndRentModal(): void
    {
        $this->showTenantEndRentModal = false;
    }

    public function endRent(): void
    {
        app(RentCycleService::class)->endRentSafely(
            rent: $this->activeRent,
            endDate: Carbon::parse($this->endDate),
            note: $this->endNote
        );

        $this->closeTenantEndRentModal();
        $this->reloadData();
    }

    /* =========================
        PAYMENT
    ========================== */
    public function openTenantPaymentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant does not have an active rent.',
            ]);
        }

        $this->amount = 0;
        $this->note   = '';
        $this->paidAt = now()->toDateString();

        $this->showTenantPaymentModal = true;
    }

    public function closeTenantPaymentModal(): void
    {
        $this->showTenantPaymentModal = false;
    }

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
            paidAt: $this->paidAt,
            note: $this->note
        );

        $this->closeTenantPaymentModal();
        $this->reloadData();
    }

    public function render()
    {
        return view('livewire.tenant-detail')
            ->layout('layouts.dashboard');
    }
}
