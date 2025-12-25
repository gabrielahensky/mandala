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
        DOMAIN STATE (EXPLICIT)
    ========================== */
    public $activeRent = null;
    public Collection $activeInvoices;
    public Collection $rentTransactions;
    public ?array $tenantBillingSummary = null;

    /* =========================
        UI STATE
    ========================== */

    // Payment
    public bool $showPaymentModal = false;
    public int $amount = 0;
    public string $paidAt;
    public string $note = '';

    // Assign unit
    public bool $showAssignUnitModal = false;
    public ?int $selectedUnitId = null;
    public string $startDate;

    // End rent
    public bool $showEndRentModal = false;
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
        CORE RELOAD (WAJIB)
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
            ? RentBilling::query()
                ->where('rent_cycle_id', $this->activeRent->id)
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
        DERIVED HELPERS (SAFE)
    ========================== */

    public function getAvailableUnitsProperty()
    {
        return Unit::query()
            ->whereDoesntHave('activeRent')
            ->orderBy('name')
            ->get();
    }

    /* =========================
        ASSIGN UNIT
    ========================== */

    public function openAssignUnitModal(): void
    {
        if ($this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant already has an active rent.',
            ]);
        }

        $this->selectedUnitId = null;
        $this->startDate = now()->toDateString();
        $this->showAssignUnitModal = true;
    }

    public function assignUnit(): void
    {
        $this->validate([
            'selectedUnitId' => 'required|exists:units,id',
            'startDate'      => 'required|date',
        ]);

        $unit = Unit::findOrFail($this->selectedUnitId);

        app(RentCycleService::class)->startRent(
            unit: $unit,
            tenant: $this->tenant,
            startDate: $this->startDate
        );

        $this->showAssignUnitModal = false;
        $this->reloadData();
    }

    /* =========================
        END RENT
    ========================== */

    public function openEndRentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant is not currently renting.',
            ]);
        }

        $this->endDate = now()->toDateString();
        $this->endNote = '';
        $this->showEndRentModal = true;
    }

    public function endRent(): void
    {
        app(RentCycleService::class)->endRentSafely(
            rent: $this->activeRent,
            endDate: Carbon::parse($this->endDate),
            note: $this->endNote
        );

        $this->showEndRentModal = false;
        $this->reloadData();
    }

    /* =========================
        PAYMENT
    ========================== */

    public function openPaymentModal(): void
    {
        if (! $this->activeRent) {
            throw ValidationException::withMessages([
                'rent' => 'Tenant does not have an active rent.',
            ]);
        }

        $this->resetPaymentForm();
        $this->showPaymentModal = true;
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

        $this->showPaymentModal = false;
        $this->resetPaymentForm();
        $this->reloadData();
    }

    protected function resetPaymentForm(): void
    {
        $this->amount = 0;
        $this->note   = '';
        $this->paidAt = now()->toDateString();
    }

    /* =========================
        RENDER
    ========================== */

    public function render()
    {
        return view('livewire.tenant-detail')
            ->layout('layouts.dashboard');
    }
}
