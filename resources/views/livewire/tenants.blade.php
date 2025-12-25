<div class="px-6 py-6 space-y-8">

    {{-- =========================
        HEADER
    ========================== --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Tenants
            </h1>
            <p class="text-sm text-gray-500">
                People who currently or previously rent your units
            </p>
        </div>

        <button
            wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium
                   bg-gray-900 text-white rounded-lg hover:bg-gray-800">
            + Add Tenant
        </button>
    </div>

    {{-- =========================
        LIST
    ========================== --}}
    @if ($tenants->count())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tenants as $tenant)

                @php
                    $activeRent = $tenant->rentCycles
                        ->firstWhere('end_date', null);
                    $activeUnit = $activeRent?->unit;
                @endphp

                <div class="bg-white border rounded-xl p-4 hover:shadow-sm transition">

                    <div class="flex items-start justify-between">
                        <div>
                            <a href="/tenants/{{ $tenant->id }}"
                               class="font-semibold text-gray-900 hover:underline">
                                {{ $tenant->name }}
                            </a>

                            {{-- STATUS --}}
                            <div class="mt-1">
                                @if ($activeRent)
                                    <span class="inline-flex items-center gap-1 text-xs
                                                 bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                        ● Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs
                                                 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                        Inactive
                                    </span>
                                @endif
                            </div>

                            {{-- PHONE --}}
                            @if ($tenant->phone)
                                <p class="text-xs text-gray-500 mt-1">
                                    📞 {{ $tenant->phone }}
                                </p>
                            @endif
                        </div>

                        {{-- ACTIONS --}}
                        <div class="flex gap-2">
                            <button
                                wire:click="edit({{ $tenant->id }})"
                                class="text-xs text-blue-600 hover:underline">
                                Edit
                            </button>

                            @if (! $activeRent)
                                <button
                                    wire:click="askDelete({{ $tenant->id }})"
                                    class="text-xs text-red-600 hover:underline">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ACTIVE UNIT --}}
                    @if ($activeUnit)
                        <div class="mt-3 text-sm bg-gray-50 border rounded-lg px-3 py-2">
                            <span class="text-xs text-gray-500">
                                Currently renting
                            </span>
                            <div class="font-medium text-gray-800">
                                {{ $activeUnit->name }}
                            </div>
                        </div>
                    @endif

                    {{-- NOTE --}}
                    @if ($tenant->note)
                        <div class="mt-3 text-sm text-gray-600 border-t pt-3">
                            {{ $tenant->note }}
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @else
        {{-- EMPTY STATE --}}
        <div class="bg-white border rounded-xl py-16 text-center">
            <p class="text-gray-500 text-sm">
                No tenants yet.
            </p>
            <button
                wire:click="create"
                class="mt-4 inline-flex items-center px-4 py-2 text-sm
                       bg-gray-900 text-white rounded-lg">
                + Add your first tenant
            </button>
        </div>
    @endif


    {{-- =========================
        CREATE / EDIT MODAL
    ========================== --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 space-y-6">

                <div>
                    <h2 class="text-lg font-semibold">
                        {{ $editingId ? 'Edit Tenant' : 'Add Tenant' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Basic tenant information
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Name
                        </label>
                        <input
                            type="text"
                            wire:model.defer="name"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            placeholder="Tenant name"
                        />
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Phone
                        </label>
                        <input
                            type="text"
                            wire:model.defer="phone"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Note
                        </label>
                        <textarea
                            wire:model.defer="note"
                            rows="3"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button
                        wire:click="closeModal"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        wire:click="save"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded-lg">
                        Save
                    </button>
                </div>

            </div>
        </div>
    @endif


    {{-- =========================
        DELETE CONFIRM MODAL
    ========================== --}}
    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl p-6 space-y-4">

                <h2 class="text-lg font-semibold text-red-600">
                    Delete Tenant
                </h2>

                <p class="text-sm text-gray-600">
                    This tenant will be archived.
                    Rental and payment history will be preserved.
                </p>

                <div class="flex justify-end gap-2 pt-4">
                    <button
                        wire:click="$set('confirmingDelete', false)"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        wire:click="confirmDelete"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">
                        Yes, delete
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
