<div class="px-6 py-6 space-y-6">

    <h1 class="text-2xl font-bold">
        Units
    </h1>

    <!-- FORM -->
    <div class="bg-white rounded-xl border p-4 space-y-3 max-w-xl">
        <h2 class="font-semibold text-sm">
            {{ $editingId ? 'Edit Unit' : 'Add Unit' }}
        </h2>

        <div class="space-y-2">
            <input
                type="text"
                wire:model.defer="name"
                placeholder="Unit name (e.g. A1)"
                class="w-full border rounded px-3 py-2 text-sm"
            />

            <textarea
                wire:model.defer="note"
                placeholder="Note (optional)"
                class="w-full border rounded px-3 py-2 text-sm"
                rows="2"
            ></textarea>

            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    wire:model="is_active"
                />
                Active
            </label>
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
                    <th class="px-4 py-2 text-left">Note</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">
                            {{ $unit->name }}
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ $unit->note }}
                        </td>
                        <td class="px-4 py-2">
                            @if ($unit->is_active)
                                <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded">
                                    Active
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button
                                wire:click="edit({{ $unit->id }})"
                                class="text-xs text-blue-600 hover:underline">
                                Edit
                            </button>
                            <button
                                wire:click="delete({{ $unit->id }})"
                                class="ml-2 text-xs text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            No units yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
