<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Unit;

class Units extends Component
{
    /* =========================
        Form State
    ========================== */
    public ?int $editingId = null;
    public string $name = '';
    public ?string $note = null;
    public bool $is_active = true;
    public bool $showModal = false;
    public int $base_price = 0;
    public array $facilities = [];
    public string $facilityInput = '';


    /* =========================
        Delete Confirmation
    ========================== */
    public bool $confirmingDelete = false;
    public ?int $deleteId = null;

    /* =========================
        Data
    ========================== */
    public $units;

    /* =========================
        Lifecycle
    ========================== */
    public function mount()
    {
        $this->loadUnits();
    }

    protected function loadUnits(): void
    {
        $this->units = Unit::query()
            ->with([
                'activeRent.tenant',
            ])
            ->orderBy('name')
            ->get();
    }

    /* =========================
        Create / Edit
    ========================== */
    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $unit = Unit::findOrFail($id);

        $this->editingId = $unit->id;
        $this->name = $unit->name;
        $this->note = $unit->note;
        $this->is_active = $unit->is_active;
        $this->base_price = $unit->base_price;
        $this->facilities = $unit->facilities ?? [];

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'base_price' => 'required|integer|min:0',
            'facilities' => 'array',
            'note' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Unit::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'base_price' => $this->base_price,
                'facilities' => $this->facilities,
                'note' => $this->note,
                'is_active' => $this->is_active,
            ]
        );

        $this->closeModal();
        $this->loadUnits();
    }
    public function addFacility(): void
    {
        if (trim($this->facilityInput) !== '') {
            $this->facilities[] = trim($this->facilityInput);
            $this->facilityInput = '';
        }
    }
    public function removeFacility(int $index): void
    {
        unset($this->facilities[$index]);
        $this->facilities = array_values($this->facilities);
    }
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->note = null;
        $this->base_price = 0;
        $this->facilities = [];
        $this->facilityInput = '';
        $this->is_active = true;
    }

    /* =========================
        Delete
    ========================== */
    public function askDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmingDelete = true;
    }

    public function confirmDelete(): void
    {
        Unit::findOrFail($this->deleteId)->delete();

        $this->confirmingDelete = false;
        $this->deleteId = null;

        $this->loadUnits();
    }

    /* =========================
        Render
    ========================== */
    public function render()
    {
        return view('livewire.units')
            ->layout('layouts.dashboard');
    }
}
