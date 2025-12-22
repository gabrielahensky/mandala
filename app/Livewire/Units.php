<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Unit;

class Units extends Component
{
    // ===== Form State =====
    public ?int $editingId = null;
    public string $name = '';
    public ?string $note = null;
    public bool $is_active = true;

    // ===== Data =====
    public $units;

    public function mount()
    {
        $this->loadUnits();
    }

    protected function loadUnits()
    {
        $this->units = Unit::orderBy('name')->get();
    }

    /* =========================
        Actions
    ========================== */

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:50',
            'note' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Unit::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'note' => $this->note,
                'is_active' => $this->is_active,
            ]
        );

        $this->resetForm();
        $this->loadUnits();
    }

    public function edit(int $id)
    {
        $unit = Unit::findOrFail($id);

        $this->editingId = $unit->id;
        $this->name = $unit->name;
        $this->note = $unit->note;
        $this->is_active = $unit->is_active;
    }

    public function delete(int $id)
    {
        Unit::findOrFail($id)->delete();
        $this->loadUnits();
    }

    protected function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->note = null;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.units')
            ->layout('layouts.dashboard');
    }
}
