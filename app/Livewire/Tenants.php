<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\ValidationException;
use App\Models\Tenant;

class Tenants extends Component
{
    /* =========================
        Form State
    ========================== */
    public ?int $editingId = null;
    public string $name = '';
    public ?string $phone = null;
    public ?string $note = null;

    public bool $showModal = false;

    /* =========================
        Delete Confirmation
    ========================== */
    public bool $confirmingDelete = false;
    public ?int $deleteId = null;

    /* =========================
        Data
    ========================== */
    public $tenants;

    /* =========================
        Lifecycle
    ========================== */
    public function mount()
    {
        $this->loadTenants();
    }

    protected function loadTenants(): void
    {
        $this->tenants = Tenant::query()
        ->whereNull('deleted_at')
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
        $tenant = Tenant::findOrFail($id);

        $this->editingId = $tenant->id;
        $this->name = $tenant->name;
        $this->phone = $tenant->phone;
        $this->note = $tenant->note;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'note'  => 'nullable|string|max:255',
        ]);

        Tenant::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name'  => $this->name,
                'phone' => $this->phone,
                'note'  => $this->note,
            ]
        );

        $this->closeModal();
        $this->loadTenants();
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
        $this->phone = null;
        $this->note = null;
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
        $tenant = Tenant::withTrashed()->findOrFail($this->deleteId);

        // 🚫 GUARD: tenant pernah / sedang sewa
        if ($tenant->rentCycles()->exists()) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant already has rental history and cannot be deleted.',
            ]);
        }

        // SAFE: soft delete
        $tenant->delete();

        $this->confirmingDelete = false;
        $this->deleteId = null;

        $this->loadTenants();
    }

    /* =========================
        Render
    ========================== */
    public function render()
    {
        return view('livewire.tenants')
            ->layout('layouts.dashboard');
    }
}
