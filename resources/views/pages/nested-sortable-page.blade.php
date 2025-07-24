<x-filament-panels::page>
    <div
        x-ref="rootSortableContainer"
        x-data="{
            pendingRecordUpdates: [],
            updateRecordPosition(event) {
                {{-- It seems inefficient to send the entire nest of records to the server. --}}
                {{-- We have to update more than just the changed record because it effects the order of other items in the nest --}}
        
                // Get all sortable groups
                const sortableGroups = this.$refs.rootSortableContainer.querySelectorAll('[x-sortable]');
        
                // Build complete current state
                this.pendingRecordUpdates = [];
        
                sortableGroups.forEach(group => {
                    const parentId = group.getAttribute('data-parent-id');
                    const recordIds = group.sortable.toArray(); // Gets current order of record IDs
        
                    recordIds.forEach((recordId, index) => {
                        this.pendingRecordUpdates.push({
                            id: recordId,
                            parent_id: parentId,
                            order: index
                        });
                    });
                });
            }
        }"
        x-on:reset-pending-record-updates.window="pendingRecordUpdates = []"
        {{-- x-on:persist-pending-record-updates.window="$wire.persistRecordUpdates(pendingRecordUpdates)" --}}>

        <div
            x-bind:class="pendingRecordUpdates.length === 0 ? 'invisible' : ''"
            class="flex justify-between items-center p-4 mb-4 space-x-4 bg-white rounded border">
            <div class="flex-1 text-sm text-gray-500">
                You have unsaved changes.
            </div>

            <x-filament::button
                color="gray"
                x-on:click="pendingRecordUpdates = [];">
                Discard changes
            </x-filament::button>

            <x-filament::button
                x-on:click="
                    $wire.persistRecordUpdates(pendingRecordUpdates).then(() => {
                        new FilamentNotification()
                            .title('Saved successfully')
                            .success()
                            .send()
                        pendingRecordUpdates = [];
                    });
                ">
                Update records
            </x-filament::button>
        </div>
        <div {{-- Using Filament Alpine directive of SortableJS --}}
            {{-- https://github.com/filamentphp/filament/blob/3.x/packages/support/resources/js/sortable.js --}}
            x-sortable
            x-sortable-group="nested-sortable"
            x-on:end.stop="updateRecordPosition(event)"
            data-parent-id="-1">

            @foreach ($records as $record)
                {{-- Only show root level records, the children are rendered by the nested-record component --}}
                @if ($record->parent_id == -1)
                    <x-filament-nested-sortable::nested-record :record="$record" />
                @endif
            @endforeach
        </div>
    </div>

</x-filament-panels::page>
