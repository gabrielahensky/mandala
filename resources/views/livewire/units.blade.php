<div class="px-6 py-6 space-y-8">

    {{-- =========================
        HEADER
    ========================== --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Units</h1>
            <p class="text-sm text-gray-500">Rooms overview & occupancy</p>
        </div>

        <button
            type="button"
            wire:click="create"
            class="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-800">
            + Add Unit
        </button>
    </div>

    {{-- =========================
        UNITS GRID
    ========================== --}}
    @if ($units->isNotEmpty())
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @foreach ($units as $unit)
                @php
                    $isOccupied = (bool) $unit->activeRent;
                    $isInactive = ! $unit->is_active;
                @endphp

                <div class="bg-white border rounded-xl p-5 space-y-4">

                    {{-- HEADER --}}
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg font-semibold">
                            {{ $unit->name }}
                        </h3>

                        {{-- STATUS --}}
                        @if ($isOccupied)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">
                                Occupied
                            </span>
                        @elseif ($isInactive)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">
                                Inactive
                            </span>
                        @else
                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">
                                Available
                            </span>
                        @endif
                    </div>

                    {{-- PRICE --}}
                    <p class="text-sm text-gray-600">
                        Rp {{ number_format($unit->base_price) }} / month
                    </p>

                    {{-- FACILITY PREVIEW --}}
                    @if (!empty($unit->facilities))
                        <div class="flex flex-wrap gap-1">
                            @foreach (array_slice($unit->facilities, 0, 3) as $facility)
                                <span class="px-2 py-0.5 text-xs bg-gray-100 rounded-full">
                                    {{ $facility }}
                                </span>
                            @endforeach

                            @if (count($unit->facilities) > 3)
                                <span class="text-xs text-gray-500">
                                    +{{ count($unit->facilities) - 3 }} more
                                </span>
                            @endif
                        </div>
                    @endif

                    {{-- OCCUPANCY INFO --}}
                    @if ($isOccupied)
                        <div class="pt-3 border-t space-y-1 text-sm">
                            <p class="text-xs text-gray-500">Occupied by</p>

                            <a
                                href="/tenants/{{ $unit->activeRent->tenant->id }}"
                                class="font-medium text-blue-600 hover:underline">
                                {{ $unit->activeRent->tenant->name }}
                            </a>

                            <p class="text-xs text-gray-500">
                                Since {{ $unit->activeRent->start_date->format('d M Y') }}
                            </p>
                        </div>
                    @endif

                    {{-- ACTIONS --}}
                    <div class="pt-3 border-t flex justify-end gap-3 text-xs">
                        <button
                            type="button"
                            wire:click="openUnitDetail({{ $unit->id }})"
                            class="text-gray-700 hover:underline">
                            Details
                        </button>

                        <button
                            type="button"
                            wire:click="edit({{ $unit->id }})"
                            class="text-blue-600 hover:underline">
                            Edit
                        </button>

                        @if (! $isOccupied)
                            <button
                                type="button"
                                wire:click="askDelete({{ $unit->id }})"
                                class="text-red-600 hover:underline">
                                Delete
                            </button>
                        @endif
                    </div>

                </div>
            @endforeach

        </section>
    @else
        <div class="bg-white border rounded-xl py-16 text-center">
            <p class="text-sm text-gray-500">No units available</p>
        </div>
    @endif

    {{-- =========================
        UNIT DETAIL MODAL
    ========================== --}}
    @if ($showUnitDetailModal && $selectedUnit)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-lg rounded-xl shadow-xl p-6 space-y-6">

                {{-- HEADER --}}
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Unit {{ $selectedUnit->name }}
                        </h2>
                        <p class="text-sm text-gray-500">Unit details</p>
                    </div>

                    <button
                        type="button"
                        wire:click="closeUnitDetail"
                        class="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>

                {{-- STATUS --}}
                <span class="px-2 py-0.5 text-xs rounded-full
                    {{ $selectedUnit->activeRent
                        ? 'bg-red-100 text-red-700'
                        : ($selectedUnit->is_active
                            ? 'bg-green-100 text-green-700'
                            : 'bg-gray-200 text-gray-600') }}">
                    {{ $selectedUnit->activeRent
                        ? 'Occupied'
                        : ($selectedUnit->is_active ? 'Available' : 'Inactive') }}
                </span>

                {{-- PRICE --}}
                <div class="text-sm">
                    <p class="text-xs text-gray-500">Monthly Rent</p>
                    <p class="font-medium">
                        Rp {{ number_format($selectedUnit->base_price) }}
                    </p>
                </div>

                {{-- FACILITIES --}}
                <div>
                    <p class="text-xs text-gray-500 mb-2">Facilities</p>

                    @if (!empty($selectedUnit->facilities))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($selectedUnit->facilities as $facility)
                                <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">
                                    {{ $facility }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No facilities listed</p>
                    @endif
                </div>

                {{-- TENANT --}}
                @if ($selectedUnit->activeRent)
                    <div class="border-t pt-4 space-y-1 text-sm">
                        <p class="text-xs text-gray-500">Tenant</p>

                        <a
                            href="/tenants/{{ $selectedUnit->activeRent->tenant->id }}"
                            class="font-medium text-blue-600 hover:underline">
                            {{ $selectedUnit->activeRent->tenant->name }}
                        </a>

                        <p class="text-xs text-gray-500">
                            Renting since {{ $selectedUnit->activeRent->start_date->format('d M Y') }}
                        </p>
                    </div>
                @endif

                {{-- ACTION --}}
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        wire:click="closeUnitDetail"
                        class="px-3 py-2 text-sm text-gray-600">
                        Close
                    </button>

                    <button
                        type="button"
                        wire:click="editFromDetail({{ $selectedUnit->id }})"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded-lg">
                        Edit Unit
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- =========================
        UNIT FORM MODAL
    ========================== --}}
    @if ($showUnitFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-6 space-y-5">

                <div>
                    <h2 class="text-lg font-semibold">
                        {{ $editingUnitId ? 'Edit Unit' : 'Add Unit' }}
                    </h2>
                    <p class="text-sm text-gray-500">Unit information</p>
                </div>

                <div class="space-y-4">

                    <div>
                        <label class="text-xs text-gray-600">Unit Name</label>
                        <input
                            type="text"
                            wire:model.defer="name"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Base Price</label>
                        <input
                            type="number"
                            wire:model.defer="base_price"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Facilities</label>

                        <div class="flex gap-2">
                            <input
                                type="text"
                                wire:model.defer="facilityInput"
                                class="flex-1 border rounded-lg px-3 py-2 text-sm" />

                            <button
                                type="button"
                                wire:click="addFacility"
                                class="px-3 py-2 bg-gray-900 text-white rounded-lg text-sm">
                                +
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($facilities as $i => $facility)
                                <span class="px-2 py-1 bg-gray-100 rounded-full text-xs flex items-center gap-1">
                                    {{ $facility }}
                                    <button
                                        type="button"
                                        wire:click="removeFacility({{ $i }})"
                                        class="text-red-500">
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-600">Note</label>
                        <textarea
                            wire:model.defer="note"
                            rows="2"
                            class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="is_active" />
                        Active
                    </label>

                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        wire:click="closeUnitForm"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        type="button"
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
    @if ($showDeleteConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-sm rounded-xl shadow-lg p-6 space-y-4">

                <h2 class="text-lg font-semibold text-red-600">
                    Delete Unit
                </h2>

                <p class="text-sm text-gray-600">
                    This unit will be archived.
                    It cannot be deleted if currently rented.
                </p>

                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="confirmDelete"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">
                        Yes, delete
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
