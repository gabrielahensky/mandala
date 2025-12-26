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

    /* =====================================================
        DOMAIN STATE (READ ONLY)
    ====================================================== */
    public $activeRent = null;
    public Collection $activeInvoices;
    public Collection $rentTransactions;
    public ?array $tenantBillingSummary = null;

    /* =====================================================
        UI STATE
    ====================================================== */
    public bool $showPaymentModal    = false;
    public bool $showAssignUnitModal = false;
    public bool $showEndRentModal    = false;

    /* =====================================================
        PAYMENT FORM
    ====================================================== */
    public int $amount = 0;
    public string $paidAt;
    public string $note = '';

    /* =====================================================
        ASSIGN UNIT FORM
    ====================================================== */
    public ?int $selectedUnitId = null;
    public string $startDate;

    /* =====================================================
        END RENT FORM
    ====================================================== */
    public string $endDate;
    public string $endNote = '';

    /* =====================================================
        LIFECYCLE
    ====================================================== */
    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;

        $today = now()->toDateString();

        $this->paidAt    = $today;
        $this->startDate = $today;
        $this->endDate   = $today;

        $this->reloadData();
    }

    /* =====================================================
        CORE DOMAIN RELOAD (WAJIB DIPANGGIL)
    ====================================================== */
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

    /* =====================================================
        DERIVED
    ====================================================== */
    public function getAvailableUnitsProperty()
    {
        return Unit::query()
            ->whereDoesntHave('activeRent')
            ->orderBy('name')
            ->get();
    }

    /* =====================================================
        ASSIGN UNIT
    ====================================================== */
    public function openAssignUnitModal(): void
    {
        if ($this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant already has an active rent.',
            ]);
        }

        $this->resetErrorBag();
        $this->selectedUnitId = null;
        $this->startDate = now()->toDateString();
        $this->showAssignUnitModal = true;
    }

    public function closeAssignUnitModal(): void
    {
        $this->showAssignUnitModal = false;
        $this->resetErrorBag();
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

        $this->closeAssignUnitModal();
        $this->reloadData();
    }

    /* =====================================================
        END RENT
    ====================================================== */
    public function openEndRentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant is not currently renting.',
            ]);
        }

        $this->resetErrorBag();
        $this->endDate = now()->toDateString();
        $this->endNote = '';
        $this->showEndRentModal = true;
    }

    public function closeEndRentModal(): void
    {
        $this->showEndRentModal = false;
        $this->resetErrorBag();
    }

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
        PAYMENT
    ====================================================== */
    public function openPaymentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant does not have an active rent.',
            ]);
        }

        $this->resetErrorBag();

        // sensible default: unpaid total
        $this->amount = $this->tenantBillingSummary['outstanding'] ?? 0;
        $this->paidAt = now()->toDateString();
        $this->note   = '';

        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
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
