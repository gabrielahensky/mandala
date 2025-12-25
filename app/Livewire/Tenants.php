<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\ValidationException;
use App\Models\Tenant;
use App\Models\RentCycle;

class Tenants extends Component
{
    /* =========================
        FORM STATE
    ========================== */
    public ?int $editingId = null;
    public string $name = '';
    public ?string $phone = null;
    public ?string $note = null;

    public bool $showModal = false;

    /* =========================
        DELETE STATE
    ========================== */
    public bool $confirmingDelete = false;
    public ?int $deleteId = null;

    /* =========================
        DATA
    ========================== */
    public $tenants;

    /* =========================
        LIFECYCLE
    ========================== */
    public function mount(): void
    {
        $this->loadTenants();
    }

    protected function loadTenants(): void
    {
        $this->tenants = Tenant::query()
            ->whereNull('deleted_at')
            ->with([
                // load ALL rent cycles, not filtered
                'rentCycles.unit',
            ])
            ->orderBy('name')
            ->get();
    }

    /* =========================
        CREATE / EDIT
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
        $this->name  = $tenant->name;
        $this->phone = $tenant->phone;
        $this->note  = $tenant->note;

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
        $this->resetErrorBag();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name  = '';
        $this->phone = null;
        $this->note  = null;
    }

    /* =========================
        DELETE
    ========================== */
    public function askDelete(int $id): void
    {
        $this->resetErrorBag();

        $this->deleteId = $id;
        $this->confirmingDelete = true;
    }

    public function confirmDelete(): void
    {
        $tenant = Tenant::findOrFail($this->deleteId);

        // 🚫 HARD GUARD — CEK LANGSUNG KE DB
        if (
            RentCycle::where('tenant_id', $tenant->id)
                ->whereNull('end_date')
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant is still renting and cannot be deleted.',
            ]);
        }

        // ✅ SOFT DELETE
        $tenant->delete();

        // RESET UI STATE
        $this->confirmingDelete = false;
        $this->deleteId = null;

        $this->loadTenants();
    }

    /* =========================
        RENDER
    ========================== */
    public function render()
    {
        return view('livewire.tenants')
            ->layout('layouts.dashboard');
    }
}
