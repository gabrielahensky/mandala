<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\ValidationException;
use App\Models\Unit;
use App\Models\RentCycle;

class Units extends Component
{
    /* =====================================================
        DATA
    ====================================================== */
    public $units;

    /* =====================================================
        UNIT DETAIL MODAL (READ ONLY)
    ====================================================== */
    public bool $showUnitDetailModal = false;
    public ?Unit $selectedUnit = null;

    /* =====================================================
        UNIT FORM MODAL (CREATE / EDIT)
    ====================================================== */
    public bool $showUnitFormModal = false;
    public ?int $editingUnitId = null;

    public string $name = '';
    public ?string $note = null;
    public int $base_price = 0;

    /**
     * true  = available for rent
     * false = inactive (maintenance / off-market)
     */
    public bool $is_active = true;

    public array $facilities = [];
    public string $facilityInput = '';

    /* =====================================================
        DELETE CONFIRMATION MODAL
    ====================================================== */
    public bool $showDeleteConfirmModal = false;
    public ?int $unitToDeleteId = null;

    /* =====================================================
        LIFECYCLE
    ====================================================== */
    public function mount(): void
    {
        $this->loadUnits();
    }

    protected function loadUnits(): void
    {
        $this->units = Unit::query()
            ->with('activeRent.tenant')
            ->orderBy('name')
            ->get();
    }

    /* =====================================================
        UNIT DETAIL MODAL
    ====================================================== */
    public function openUnitDetail(int $unitId): void
    {
        $this->selectedUnit = Unit::with('activeRent.tenant')
            ->findOrFail($unitId);

        $this->showUnitDetailModal = true;
    }

    public function closeUnitDetail(): void
    {
        $this->showUnitDetailModal = false;
        $this->selectedUnit = null;
    }

    /**
     * IMPORTANT:
     * Transition from Detail → Edit
     */
    public function editFromDetail(int $unitId): void
    {
        $this->closeUnitDetail();
        $this->edit($unitId);
    }

    /* =====================================================
        CREATE / EDIT FLOW
    ====================================================== */
    public function create(): void
    {
        $this->resetUnitForm();
        $this->showUnitFormModal = true;
    }

    public function edit(int $unitId): void
    {
        $unit = Unit::with('activeRent')->findOrFail($unitId);

        $this->editingUnitId = $unit->id;
        $this->name          = $unit->name;
        $this->note          = $unit->note;
        $this->base_price    = $unit->base_price;
        $this->is_active     = $unit->is_active;
        $this->facilities    = $unit->facilities ?? [];

        $this->showUnitFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:50',
            'base_price'  => 'required|integer|min:0',
            'facilities'  => 'array',
            'note'        => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        /* =========================
            DOMAIN GUARD
        ========================== */
        if ($this->editingUnitId) {
            $unit = Unit::with('activeRent')->findOrFail($this->editingUnitId);

            // ❌ cannot deactivate occupied unit
            if (! $this->is_active && $unit->activeRent) {
                throw ValidationException::withMessages([
                    'is_active' => 'Cannot deactivate a unit that is currently rented.',
                ]);
            }
        }

        Unit::updateOrCreate(
            ['id' => $this->editingUnitId],
            [
                'name'        => $this->name,
                'base_price'  => $this->base_price,
                'facilities'  => $this->facilities,
                'note'        => $this->note,
                'is_active'   => $this->is_active,
            ]
        );

        $this->closeUnitForm();
        $this->loadUnits();
    }

    public function closeUnitForm(): void
    {
        $this->showUnitFormModal = false;
        $this->resetUnitForm();
        $this->resetErrorBag();
    }

    protected function resetUnitForm(): void
    {
        $this->editingUnitId = null;
        $this->name = '';
        $this->note = null;
        $this->base_price = 0;
        $this->is_active = true;
        $this->facilities = [];
        $this->facilityInput = '';
    }

    /* =====================================================
        FACILITIES
    ====================================================== */
    public function addFacility(): void
    {
        $value = trim($this->facilityInput);

        if ($value !== '') {
            $this->facilities[] = $value;
            $this->facilityInput = '';
        }
    }

    public function removeFacility(int $index): void
    {
        unset($this->facilities[$index]);
        $this->facilities = array_values($this->facilities);
    }

    /* =====================================================
        DELETE FLOW
    ====================================================== */
    public function askDelete(int $unitId): void
    {
        $this->resetErrorBag();

        $this->unitToDeleteId = $unitId;
        $this->showDeleteConfirmModal = true;
    }

    public function cancelDelete(): void
    {
        $this->unitToDeleteId = null;
        $this->showDeleteConfirmModal = false;
        $this->resetErrorBag();
    }

    public function confirmDelete(): void
    {
        $unit = Unit::with('activeRent')->findOrFail($this->unitToDeleteId);

        // ❌ cannot delete occupied unit
        if ($unit->activeRent) {
            throw ValidationException::withMessages([
                'unit' => 'Unit is currently rented and cannot be deleted.',
            ]);
        }

        $unit->delete();

        $this->cancelDelete();
        $this->loadUnits();
    }

    /* =====================================================
        RENDER
    ====================================================== */
    public function render()
    {
        return view('livewire.units')
            ->layout('layouts.dashboard');
    }
}
