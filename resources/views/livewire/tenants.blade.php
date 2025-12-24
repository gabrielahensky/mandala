<div class="px-6 py-6 space-y-8">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Tenants
            </h1>
            <p class="text-sm text-gray-500">
                Manage people who rent your units
            </p>
        </div>

        <button
            wire:click="create"
            class="px-4 py-2 bg-gray-900 text-white text-sm rounded">
            + Add Tenant
        </button>
    </div>

    {{-- LIST --}}
    <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Phone</th>
                    <th class="px-4 py-2 text-left">Note</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">
                            <a href="/tenants/{{ $tenant->id }}"
                               class="text-blue-600 hover:underline">
                                {{ $tenant->name }}
                            </a>
                        </td>

                        <td class="px-4 py-2 text-gray-500">
                            {{ $tenant->phone ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-gray-500">
                            {{ $tenant->note ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-right space-x-3">
                            <button
                                wire:click="edit({{ $tenant->id }})"
                                class="text-xs text-blue-600 hover:underline">
                                Edit
                            </button>

                            <button
                                wire:click="askDelete({{ $tenant->id }})"
                                class="text-xs text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4"
                            class="px-4 py-8 text-center text-gray-500">
                            No tenants yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white w-full max-w-md rounded-xl shadow-lg p-6 space-y-5">

                <div>
                    <h2 class="text-lg font-semibold">
                        {{ $editingId ? 'Edit Tenant' : 'Add Tenant' }}
                    </h2>
                    <p class="text-xs text-gray-500">
                        Tenant information
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            Name
                        </label>
                        <input
                            type="text"
                            wire:model.defer="name"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Tenant name"
                        />
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            Phone
                        </label>
                        <input
                            type="text"
                            wire:model.defer="phone"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Optional phone number"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            Note
                        </label>
                        <textarea
                            wire:model.defer="note"
                            rows="2"
                            class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Optional note"
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
            <div class="bg-white w-full max-w-sm rounded-xl shadow-lg p-6 space-y-4">

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
                        class="px-4 py-2 text-sm bg-red-600 text-white rounded">
                        Yes, delete
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
