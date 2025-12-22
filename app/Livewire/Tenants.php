<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tenant;

class Tenants extends Component
{
    // ===== Form State =====
    public ?int $editingId = null;
    public string $name = '';
    public ?string $phone = null;
    public ?string $note = null;

    // ===== Data =====
    public $tenants;

    public function mount()
    {
        $this->loadTenants();
    }

    protected function loadTenants()
    {
        $this->tenants = Tenant::orderBy('name')->get();
    }

    /* =========================
        Actions
    ========================== */

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:255',
        ]);

        Tenant::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'note' => $this->note,
            ]
        );

        $this->resetForm();
        $this->loadTenants();
    }

    public function edit(int $id)
    {
        $tenant = Tenant::findOrFail($id);

        $this->editingId = $tenant->id;
        $this->name = $tenant->name;
        $this->phone = $tenant->phone;
        $this->note = $tenant->note;
    }

    public function delete(int $id)
    {
        Tenant::findOrFail($id)->delete();
        $this->loadTenants();
    }

    protected function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = null;
        $this->note = null;
    }

    public function render()
    {
        return view('livewire.tenants')
            ->layout('layouts.dashboard');
    }
}
