<x-filament-panels::page>
    <div
        x-ref="rootSortableContainer"
        x-data="{
            pendingRecordUpdates: [],
            originalState: [],
            recordGroupMapping: {},
        
            getCurrentSortableState() {
                const sortableGroups = this.$refs.rootSortableContainer.querySelectorAll('[x-sortable]');
                const currentState = [];
                const itemMapping = {};
        
                sortableGroups.forEach(group => {
                    const parentId = group.getAttribute('data-parent-id');
                    const recordIds = group.sortable.toArray();
        
                    currentState.push({
                        parentId: parentId,
                        recordIds: [...recordIds]
                    });
        
                    // Build mapping of which group each item currently belongs to
                    recordIds.forEach(recordId => {
                        itemMapping[recordId] = parentId;
                    });
                });
        
                return { currentState, itemMapping };
            },
        
            // Initialize original state on page load
            init() {
                $nextTick(() => {
                    this.captureOriginalState();
                })
            },
        
            // Enhanced capture original state
            captureOriginalState() {
                const { currentState, itemMapping } = this.getCurrentSortableState();
                this.originalState = currentState;
                this.recordGroupMapping = itemMapping;
            },
        
            // Enhanced revert with two-phase process
            revertToOriginalState() {
                const sortableGroups = this.$refs.rootSortableContainer.querySelectorAll('[x-sortable]');
        
                this.moveItemsToOriginalGroups(sortableGroups);
        
                this.restoreOriginalOrder(sortableGroups);
        
                this.pendingRecordUpdates = [];
            },
        
            // Phase 1: Move items to their original groups
            moveItemsToOriginalGroups(sortableGroups) {
                const currentState = this.getCurrentSortableState();
        
                // Find items that are in the wrong group
                Object.entries(this.recordGroupMapping).forEach(([itemId, originalParentId]) => {
                    const currentParentId = currentState.itemMapping[itemId];
        
                    if (currentParentId !== originalParentId) {
                        // Find the item element
                        const itemElement = this.$refs.rootSortableContainer.querySelector('[data-item-id=' + JSON.stringify(itemId) + ']');
        
                        if (itemElement) {
                            // Find the target group
                            const targetGroup = Array.from(sortableGroups).find(g =>
                                g.getAttribute('data-parent-id') === originalParentId
                            );
        
                            if (targetGroup) {
                                // Move the item to the target group
                                targetGroup.appendChild(itemElement);
                            }
                        }
                    }
                });
            },
        
            restoreOriginalOrder(sortableGroups) {
                this.originalState.forEach(originalGroup => {
                    const group = Array.from(sortableGroups).find(g =>
                        g.getAttribute('data-parent-id') === originalGroup.parentId
                    );
        
                    if (group && group.sortable) {
                        group.sortable.sort(originalGroup.recordIds);
                    }
                });
            },
        
            updateRecordPosition(event) {
                {{-- It seems inefficient to send the entire nest of records to the server. --}}
                {{-- We have to update more than just the changed record because it effects the order of other items in the nest --}}
        
                const { currentState } = this.getCurrentSortableState();
        
                // Build complete current state
                this.pendingRecordUpdates = [];
        
                currentState.forEach(group => {
                    group.recordIds.forEach((recordId, index) => {
                        this.pendingRecordUpdates.push({
                            id: recordId,
                            parent_id: group.parentId,
                            order: index
                        });
                    });
                });
        
            }
        }"
        x-on:reset-pending-record-updates.window="pendingRecordUpdates = []">

        <div
            x-bind:class="pendingRecordUpdates.length > 0 ? '' : 'invisible'"
            class="flex justify-between items-center p-4 mb-4 space-x-4 bg-white rounded border">
            <div class="flex-1 text-sm text-gray-500">
                You have unsaved changes.
            </div>

            <x-filament::button
                color="gray"
                x-on:click="revertToOriginalState()">
                Discard changes
            </x-filament::button>

            <x-filament::button
                x-on:click="
                    $wire.persistRecordUpdates(pendingRecordUpdates).then(() => {
                        new FilamentNotification()
                            .title('Changes saved successfully')
                            .success()
                            .send()
                        pendingRecordUpdates = [];
                    });
                ">
                Save changes
            </x-filament::button>
        </div>
        <div
            {{-- Using Filament Alpine directive of SortableJS --}}
            {{-- https://github.com/filamentphp/filament/blob/3.x/packages/support/resources/js/sortable.js --}}
            x-sortable
            x-sortable-group="nested-sortable"
            x-on:end="$dispatch('updated-record-position'); updateRecordPosition(event)"
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
