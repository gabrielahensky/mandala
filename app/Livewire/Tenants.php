<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\ValidationException;
use App\Models\Tenant;
use App\Models\RentCycle;
use Illuminate\Support\Collection;

class Tenants extends Component
{
    /* =====================================================
        DATA
    ====================================================== */
    public Collection $tenants;

    /* =====================================================
        TENANT FORM STATE (CREATE / EDIT)
    ====================================================== */
    public bool $showTenantFormModal = false;
    public ?int $editingTenantId = null;

    public string $name = '';
    public ?string $phone = null;
    public ?string $note = null;

    /* =====================================================
        DELETE CONFIRMATION
    ====================================================== */
    public bool $showDeleteConfirmModal = false;
    public ?int $tenantToDeleteId = null;

    /* =====================================================
        LIFECYCLE
    ====================================================== */
    public function mount(): void
    {
        $this->reloadTenants();
    }

    /* =====================================================
        CORE DATA LOADER (SINGLE SOURCE OF TRUTH)
    ====================================================== */
    protected function reloadTenants(): void
    {
        $this->tenants = Tenant::query()
            ->whereNull('deleted_at')
            ->with([
                'activeRent.unit', 
            ])
            ->orderBy('name')
            ->get();
    }

    /* =====================================================
        DERIVED HELPERS (SAFE FOR BLADE)
    ====================================================== */
    public function tenantHasActiveRent(Tenant $tenant): bool
    {
        return $tenant->rentCycles->isNotEmpty();
    }

    public function tenantActiveUnit(Tenant $tenant)
    {
        return $tenant->rentCycles->first()?->unit;
    }

    /* =====================================================
        CREATE / EDIT
    ====================================================== */
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

        $this->closeTenantForm();
        $this->reloadTenants();
    }

    public function closeTenantForm(): void
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

    /* =====================================================
        DELETE FLOW
    ====================================================== */
    public function askDelete(int $tenantId): void
    {
        $this->resetErrorBag();

        $this->tenantToDeleteId = $tenantId;
        $this->showDeleteConfirmModal = true;
    }

    public function cancelDelete(): void
    {
        $this->tenantToDeleteId = null;
        $this->showDeleteConfirmModal = false;
        $this->resetErrorBag();
    }

    public function confirmDelete(): void
    {
        $tenant = Tenant::findOrFail($this->tenantToDeleteId);

        // 🚫 HARD DOMAIN GUARD — DB LEVEL
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

        $this->cancelDelete();
        $this->reloadTenants();
    }

    /* =====================================================
        RENDER
    ====================================================== */
    public function render()
    {
        return view('livewire.tenants')
            ->layout('layouts.dashboard');
    }
}
