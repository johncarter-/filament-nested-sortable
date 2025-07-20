<x-filament-panels::page>
    <div class="space-y-6">
        <div
            x-data="{
                items: {{ Js::from($this->getTreeItems()) }},
                originalItems: {{ Js::from($this->getTreeItems()) }},
                workingItems: {{ Js::from($this->getTreeItems()) }},
                renderKey: 0,
                hasChanges: false,
                init() {
                    this.$watch('workingItems', (newItems) => {
                        this.checkForChanges();
                    }, { deep: true });
            
                    // Store reference to this component globally for sortable callbacks
                    window.treeComponent = this;
                },
                checkForChanges() {
                    this.hasChanges = JSON.stringify(this.workingItems) !== JSON.stringify(this.originalItems);
                    // Update Livewire component
                    $wire.call('setHasChanges', this.hasChanges);
                },
                saveChanges() {
                    // This function is called only when the user clicks the Save button
                    // Drag and drop operations only update the working copy in memory
            
                    // Call the backend to persist changes to database
                    $wire.call('persistTreeChanges', this.workingItems).then(() => {
                        // Update local arrays after successful save
                        this.items = JSON.parse(JSON.stringify(this.workingItems));
                        this.originalItems = JSON.parse(JSON.stringify(this.workingItems));
                        this.hasChanges = false;
                        $wire.call('setHasChanges', false);
                    }).catch((error) => {
                        // You could show a notification here
                    });
                },
                discardChanges() {
                    // Reset working items to original
                    this.workingItems = JSON.parse(JSON.stringify(this.originalItems));
                    this.hasChanges = false;
                    $wire.call('setHasChanges', false);
                },
                refreshTreeItems() {
                    // Refresh tree items from the backend
                    $wire.call('getTreeItems').then((newItems) => {
                        this.items = newItems;
                        this.originalItems = JSON.parse(JSON.stringify(newItems));
                        this.workingItems = JSON.parse(JSON.stringify(newItems));
                    });
                }
            }"
            class="space-y-2"
            @save-tree-changes.window="saveChanges()"
            @discard-tree-changes.window="discardChanges()"
            @tree-changes-saved.window="refreshTreeItems()">
            <div
                x-sortable="{
                    group: 'tree-items',
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        const component = window.treeComponent;
                        
                        // Get the moved item ID
                        const movedItemId = evt.item.getAttribute('data-id');
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;
                        
                        // Create a new working array
                        const newWorkingItems = [...component.workingItems];
                        
                        // Find the moved item
                        const movedItem = newWorkingItems.find(p => p.id == movedItemId);
                        if (movedItem) {
                            // Remove the item from its old position
                            newWorkingItems.splice(oldIndex, 1);
                            // Insert it at the new position
                            newWorkingItems.splice(newIndex, 0, movedItem);
                            
                            // Update all order properties to match their new positions
                            newWorkingItems.forEach((item, index) => {
                                item.order = index;
                            });
                            
                            // Force Alpine.js to detect the change by creating a new array reference
                            component.workingItems = [...newWorkingItems];
                            
                            // Force a re-render by incrementing the render key
                            component.renderKey++;
                        }
                    }
                }"
                class="">
                <template x-for="(item, index) in workingItems" :key="`${item.id}-${item.order}-${index}-${renderKey}`">
                    <div
                        class="dark:bg-gray-800 dark:border-gray-700 -mt-px bg-white rounded border border-gray-200 shadow-sm"
                        :data-id="item.id">
                        <div class="flex items-stretch">
                            <div class="drag-handle hover:text-gray-600 dark:hover:text-gray-300 rounded-s flex justify-center items-center p-0.5 text-gray-400 bg-gray-50 cursor-move">
                                <x-filament-nested-sortable::drag-handle class="w-5 h-5" />
                            </div>
                            <div class="p-2">
                                <span x-text="item.display" class="dark:text-white text-sm font-medium text-gray-900"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            // Initialize Sortable if Alpine.js x-sortable doesn't work
            document.addEventListener('alpine:init', () => {
                Alpine.directive('sortable', (el, {
                    expression
                }, {
                    evaluate
                }) => {
                    const options = evaluate(expression);
                    const sortable = new Sortable(el, options);
                });
            });
        </script>
    @endpush

    @push('styles')
        <style>
            /* Placeholder drop spot styling */
            .sortable-ghost {
                opacity: 0.5;
                background: #f3f4f6 !important;
                border: 2px dashed #d1d5db !important;
                border-radius: 0.375rem;
                box-shadow: none !important;
            }

            /* Item being dragged */
            .sortable-drag {
                opacity: 0.8;
                transform: rotate(5deg);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            }

            /* Item selected for dragging */
            .sortable-chosen {
                background: #fef3c7 !important;
                border-color: #f59e0b !important;
            }

            /* Dark mode support */
            .dark .sortable-ghost {
                background: #374151 !important;
                border-color: #6b7280 !important;
            }

            .dark .sortable-chosen {
                background: #451a03 !important;
                border-color: #d97706 !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
