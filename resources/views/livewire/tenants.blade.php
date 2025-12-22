<div class="px-6 py-6 space-y-6">

    <h1 class="text-2xl font-bold">
        Tenants
    </h1>

    <!-- FORM -->
    <div class="bg-white rounded-xl border p-4 space-y-3 max-w-xl">
        <h2 class="font-semibold text-sm">
            {{ $editingId ? 'Edit Tenant' : 'Add Tenant' }}
        </h2>

        <div class="space-y-2">
            <input
                type="text"
                wire:model.defer="name"
                placeholder="Tenant name"
                class="w-full border rounded px-3 py-2 text-sm"
            />

            <input
                type="text"
                wire:model.defer="phone"
                placeholder="Phone (optional)"
                class="w-full border rounded px-3 py-2 text-sm"
            />

            <textarea
                wire:model.defer="note"
                placeholder="Note (optional)"
                class="w-full border rounded px-3 py-2 text-sm"
                rows="2"
            ></textarea>
        </div>

        <div class="flex gap-2">
            <button
                wire:click="save"
                class="px-4 py-2 bg-gray-900 text-white text-sm rounded">
                Save
            </button>

            @if ($editingId)
                <button
                    wire:click="resetForm"
                    class="px-3 py-2 text-sm text-gray-600">
                    Cancel
                </button>
            @endif
        </div>
    </div>

    <!-- LIST -->
    <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Phone</th>
                    <th class="px-4 py-2 text-left">Note</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">
                            {{ $tenant->name }}
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ $tenant->phone }}
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ $tenant->note }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button
                                wire:click="edit({{ $tenant->id }})"
                                class="text-xs text-blue-600 hover:underline">
                                Edit
                            </button>
                            <button
                                wire:click="delete({{ $tenant->id }})"
                                class="ml-2 text-xs text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No tenants yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
