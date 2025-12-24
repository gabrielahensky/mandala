<div class="px-6 py-6 space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Units</h1>
            <p class="text-sm text-gray-500">
                Rooms overview & occupancy
            </p>
        </div>

        <button
            wire:click="create"
            class="px-4 py-2 bg-gray-900 text-white text-sm rounded">
            + Add Unit
        </button>
    </div>

    {{-- UNITS GRID --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        @forelse ($units as $unit)
            <div class="bg-white border rounded-xl p-5 space-y-3">

                {{-- UNIT NAME --}}
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold">
                        {{ $unit->name }}
                    </h3>

                    @if ($unit->activeRent)
                        <span class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded">
                            Occupied
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">
                            Available
                        </span>
                    @endif
                </div>

                {{-- PRICE --}}
                @if ($unit->base_price)
                    <p class="text-sm text-gray-600">
                        Rent: Rp {{ number_format($unit->base_price) }} / month
                    </p>
                @endif

                {{-- NOTE --}}
                @if ($unit->note)
                    <p class="text-xs text-gray-500">
                        {{ $unit->note }}
                    </p>
                @endif

                {{-- OCCUPANT --}}
                @if ($unit->activeRent)
                    <div class="pt-3 border-t space-y-1">
                        <p class="text-xs text-gray-500">
                            Occupied by
                        </p>

                        <a
                            href="/tenants/{{ $unit->activeRent->tenant->id }}"
                            class="text-sm font-medium text-blue-600 hover:underline">
                            {{ $unit->activeRent->tenant->name }}
                        </a>

                        <p class="text-xs text-gray-500">
                            Since {{ $unit->activeRent->start_date->format('d M Y') }}
                        </p>
                    </div>
                @endif

                {{-- ACTION --}}
                <div class="pt-3 border-t flex justify-end gap-3 text-xs">
                    <button
                        wire:click="edit({{ $unit->id }})"
                        class="text-blue-600 hover:underline">
                        Edit
                    </button>

                    <button
                        wire:click="askDelete({{ $unit->id }})"
                        class="text-red-600 hover:underline">
                        Delete
                    </button>
                </div>

            </div>
        @empty
            <p class="text-sm text-gray-500">
                No units available
            </p>
        @endforelse

    </section>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-5 space-y-4">

                <h2 class="text-lg font-semibold">
                    {{ $editingId ? 'Edit Unit' : 'Add Unit' }}
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600">Unit Name</label>
                        <input
                            type="text"
                            wire:model.defer="name"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="e.g. A1"
                        />
                        @error('name')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Base Price (Monthly)</label>
                        <input
                            type="number"
                            wire:model.defer="base_price"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="1500000"
                        />
                        @error('base_price')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Facilities</label>

                        <div class="flex gap-2">
                            <input
                                type="text"
                                wire:model.defer="facilityInput"
                                class="flex-1 border rounded px-3 py-2 text-sm"
                                placeholder="AC / Wifi / Kamar Mandi Dalam"
                            />

                            <button
                                type="button"
                                wire:click="addFacility"
                                class="px-3 bg-gray-900 text-white rounded text-sm">
                                +
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($facilities as $i => $facility)
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs flex items-center gap-1">
                                    {{ $facility }}
                                    <button
                                        wire:click="removeFacility({{ $i }})"
                                        class="text-red-500">
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Note</label>
                        <textarea
                            wire:model.defer="note"
                            rows="2"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Optional"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="is_active" />
                        Active
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button
                        wire:click="closeModal"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        wire:click="save"
                        class="px-4 py-2 text-sm bg-gray-900 text-white rounded">
                        Save
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM MODAL --}}
    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-sm rounded-xl shadow-lg p-5 space-y-4">

                <h2 class="text-lg font-semibold text-red-600">
                    Delete Unit
                </h2>

                <p class="text-sm text-gray-600">
                    Are you sure you want to delete this unit?
                </p>

                <div class="flex justify-end gap-2 pt-3">
                    <button
                        wire:click="$set('confirmingDelete', false)"
                        class="px-3 py-2 text-sm text-gray-600">
                        Cancel
                    </button>

                    <button
                        wire:click="confirmDelete"
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded">
                        Yes, delete
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
