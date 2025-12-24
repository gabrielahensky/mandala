<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Unit;
use App\Models\Tenant;
use App\Services\RentCycleService;

class UnitDetail extends Component
{
    public Unit $unit;

    /* =========================
        Modal State
    ========================== */

    public bool $showStartRentModal = false;
    public bool $showEndRentModal = false;

    // Start rent form
    public ?int $tenantId = null;
    public ?int $monthlyRent = null;
    public ?string $note = null;

    /* =========================
        Lifecycle
    ========================== */

    public function mount(Unit $unit)
    {
        $this->unit = $unit->load([
            'activeRent.tenant',
        ]);
    }

    /* =========================
        Derived State
    ========================== */

    public function getActiveRentProperty()
    {
        return $this->unit->activeRent;
    }

    public function getTenantsProperty()
    {
        return Tenant::orderBy('name')->get();
    }

    /* =========================
        Start Rent
    ========================== */

    public function openStartRent()
    {
        $this->resetStartForm();
        $this->showStartRentModal = true;
    }

    public function closeStartRent()
    {
        $this->showStartRentModal = false;
        $this->resetStartForm();
    }

    protected function resetStartForm()
    {
        $this->tenantId = null;
        $this->monthlyRent = null;
        $this->note = null;
    }

    public function confirmStartRent(RentCycleService $service)
    {
        $this->validate([
            'tenantId'    => 'required|exists:tenants,id',
            'monthlyRent' => 'required|integer|min:1',
            'note'        => 'nullable|string|max:255',
        ]);

        $service->startRent(
            $this->unit,
            Tenant::findOrFail($this->tenantId),
            now(),
            $this->monthlyRent,
            $this->note
        );

        // refresh unit state
        $this->unit->refresh();

        $this->closeStartRent();
    }

    /* =========================
        End Rent
    ========================== */

    public function openEndRent()
    {
        $this->showEndRentModal = true;
    }

    public function closeEndRent()
    {
        $this->showEndRentModal = false;
    }

    public function confirmEndRent(RentCycleService $service)
    {
        $service->endRent(
            $this->activeRent,
            now()
        );

        $this->unit->refresh();
        $this->closeEndRent();
    }

    /* =========================
        Render
    ========================== */

    public function render()
    {
        return view('livewire.unit-detail')
            ->layout('layouts.dashboard');
    }
}
