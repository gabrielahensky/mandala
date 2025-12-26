<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\ValidationException;
use App\Models\Tenant;
use App\Models\RentCycle;

class Tenants extends Component
{
    /* =========================
        TENANT FORM STATE
    ========================== */
    public ?int $editingTenantId = null;
    public string $name = '';
    public ?string $phone = null;
    public ?string $note = null;

    public bool $showTenantFormModal = false;

    /* =========================
        DELETE CONFIRMATION STATE
    ========================== */
    public bool $showDeleteConfirmModal = false;
    public ?int $tenantToDeleteId = null;

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

    /* =========================
        DATA LOADER
    ========================== */
    protected function loadTenants(): void
    {
        $this->tenants = Tenant::query()
            ->whereNull('deleted_at')
            ->with([
                // Load all rent cycles + units (no filtering here)
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
        $this->resetTenantForm();
        $this->showTenantFormModal = true;
    }

    public function edit(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        $this->editingTenantId = $tenant->id;
        $this->name  = $tenant->name;
        $this->phone = $tenant->phone;
        $this->note  = $tenant->note;

        $this->showTenantFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'note'  => 'nullable|string|max:255',
        ]);

        Tenant::updateOrCreate(
            ['id' => $this->editingTenantId],
            [
                'name'  => $this->name,
                'phone' => $this->phone,
                'note'  => $this->note,
            ]
        );

        $this->closeTenantFormModal();
        $this->loadTenants();
    }

    public function closeTenantFormModal(): void
    {
        $this->showTenantFormModal = false;
        $this->resetTenantForm();
        $this->resetErrorBag();
    }

    protected function resetTenantForm(): void
    {
        $this->editingTenantId = null;
        $this->name  = '';
        $this->phone = null;
        $this->note  = null;
    }

    /* =========================
        DELETE FLOW
    ========================== */
    public function askDelete(int $tenantId): void
    {
        $this->resetErrorBag();

        $this->tenantToDeleteId = $tenantId;
        $this->showDeleteConfirmModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirmModal = false;
        $this->tenantToDeleteId = null;
        $this->resetErrorBag();
    }

    public function confirmDelete(): void
    {
        $tenant = Tenant::findOrFail($this->tenantToDeleteId);

        // HARD GUARD — must not have active rent
        if (
            RentCycle::where('tenant_id', $tenant->id)
                ->whereNull('end_date')
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'tenant' => 'Tenant is still renting and cannot be deleted.',
            ]);
        }

        // Soft delete
        $tenant->delete();

        // Reset delete state
        $this->cancelDelete();

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
