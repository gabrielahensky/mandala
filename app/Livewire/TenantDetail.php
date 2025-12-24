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
        UI State
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
        Lifecycle
    ========================== */

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant->load([
            'activeRent.unit',
        ]);

        $this->paidAt    = now()->toDateString();
        $this->startDate = now()->toDateString();
        $this->endDate   = now()->toDateString();
    }

    /* =========================
        Derived State
    ========================== */

    public function getActiveRentProperty()
    {
        return $this->tenant->activeRent;
    }

    public function getAvailableUnitsProperty()
    {
        return Unit::query()
            ->whereDoesntHave('activeRent')
            ->orderBy('name')
            ->get();
    }

    public function getActiveInvoicesProperty(): Collection
    {
        if (! $this->activeRent) {
            return collect();
        }

        return RentBilling::query()
            ->where('rent_cycle_id', $this->activeRent->id)
            ->whereNull('paid_at')
            ->orderBy('due_date')
            ->get();
    }

    public function getRentTransactionsProperty(): Collection
    {
        if (! $this->activeRent) {
            return collect();
        }

        return $this->activeRent
            ->transactions()
            ->where('category', 'Rent')
            ->orderByDesc('transacted_at')
            ->get();
    }

    public function getTenantBillingSummaryProperty(): ?array
    {
        if (! $this->activeRent) {
            return null;
        }

        return app(RentBillingService::class)
            ->summaryWithCarry($this->activeRent, now());
    }

    /* =========================
        Assign Unit
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

        $this->refreshTenant();
        $this->showAssignUnitModal = false;
    }

    /* =========================
        End Rent
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

        $this->refreshTenant();
        $this->showEndRentModal = false;
    }

    /* =========================
        Payment
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

        $this->refreshTenant();
        $this->resetPaymentForm();
        $this->showPaymentModal = false;
    }

    protected function resetPaymentForm(): void
    {
        $this->amount = 0;
        $this->note   = '';
        $this->paidAt = now()->toDateString();
    }

    protected function refreshTenant(): void
    {
        $this->tenant->refresh();
        $this->tenant->load('activeRent.unit');
    }

    /* =========================
        Render
    ========================== */

    public function render()
    {
        return view('livewire.tenant-detail')
            ->layout('layouts.dashboard');
    }
}
