<x-filament-panels::page>
    <div class="">
        <div
            x-data="{
                items: {{ Js::from($this->getTreeItems()) }},
                originalItems: {{ Js::from($this->getTreeItems()) }},
                workingItems: {{ Js::from($this->getTreeItems()) }},
                renderKey: 0,
                hasChanges: false,
                isDragging: false,
                init() {
                    this.$watch('workingItems', (newItems) => {
                        this.checkForChanges();
                    }, { deep: true });
            
                    // Store reference to this component globally for sortable callbacks
                    window.treeComponent = this;
            
                    // Initialize sortables after component is ready
                    this.$nextTick(() => {
                        this.initializeSortables();
                    });
                },
                checkForChanges() {
                    this.hasChanges = JSON.stringify(this.workingItems) !== JSON.stringify(this.originalItems);
                    // Update Livewire component
                    $wire.call('setHasChanges', this.hasChanges);
                },
                saveChanges() {
                    // This function is called only when the user clicks the Save button
                    // Drag and drop operations only update the working copy in memory
            
                    // Flatten the tree structure for backend
                    const flattenedItems = this.flattenTree(this.workingItems);
            
                    // Call the backend to persist changes to database
                    $wire.call('persistTreeChanges', flattenedItems).then(() => {
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
                        this.renderKey++;
                        this.$nextTick(() => {
                            this.initializeSortables();
                        });
                    });
                },
                // Helper function to find item by ID recursively
                findItemById(items, id) {
                    for (let item of items) {
                        if (item.id == id) return item;
                        if (item.children && item.children.length > 0) {
                            const found = this.findItemById(item.children, id);
                            if (found) return found;
                        }
                    }
                    return null;
                },
                // Helper function to remove item by ID recursively
                removeItemById(items, id) {
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].id == id) {
                            return items.splice(i, 1)[0];
                        }
                        if (items[i].children && items[i].children.length > 0) {
                            const removed = this.removeItemById(items[i].children, id);
                            if (removed) return removed;
                        }
                    }
                    return null;
                },
                // Update order values recursively
                updateOrderRecursively(items, parentId = -1) {
                    items.forEach((item, index) => {
                        item.order = index;
                        item.parent_id = parentId;
                        if (item.children && item.children.length > 0) {
                            this.updateOrderRecursively(item.children, item.id);
                        }
                    });
                },
                // Flatten tree structure for backend
                flattenTree(items, result = []) {
                    items.forEach(item => {
                        // Add this item to result (without children for the flat structure)
                        result.push({
                            id: item.id,
                            parent_id: item.parent_id,
                            order: item.order,
                            display: item.display
                        });
            
                        // Recursively add children
                        if (item.children && item.children.length > 0) {
                            this.flattenTree(item.children, result);
                        }
                    });
                    return result;
                },
                // Initialize sortables for nested containers
                initializeSortables() {
                    // Clean up existing sortables
                    document.querySelectorAll('.nested-sortable').forEach(el => {
                        if (el.sortable) {
                            el.sortable.destroy();
                        }
                    });
            
                    // Initialize new sortables
                    const containers = document.querySelectorAll('.nested-sortable');
                    containers.forEach(container => {
                        container.sortable = new Sortable(container, {
                            group: 'tree-items',
                            animation: 150,
                            handle: '.drag-handle',
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
            
                            onStart: (evt) => {
                                this.isDragging = true;
                                document.body.classList.add('is-dragging');
                            },
            
                            onEnd: (evt) => {
                                this.isDragging = false;
                                document.body.classList.remove('is-dragging');
                                this.handleSortEnd(evt);
                            }
                        });
                    });
                },
                // Handle sort end event
                handleSortEnd(evt) {
                    const movedItemId = evt.item.getAttribute('data-id');
                    const newParentContainer = evt.to;
                    const newIndex = evt.newIndex;
            
                    // Create a deep copy of working items
                    let newWorkingItems = JSON.parse(JSON.stringify(this.workingItems));
            
                    // Find and remove the moved item from its original location
                    const movedItem = this.removeItemById(newWorkingItems, movedItemId);
            
                    if (movedItem) {
                        // Determine the new parent
                        const newLevel = parseInt(newParentContainer.getAttribute('data-level') || '0');
                        let targetArray;
            
                        if (newLevel === 0) {
                            // Moving to root level
                            targetArray = newWorkingItems;
                            movedItem.parent_id = -1;
                        } else {
                            // Moving to a nested level - find parent
                            const parentElement = newParentContainer.closest('.tree-item');
                            const parentId = parentElement ? parentElement.getAttribute('data-id') : null;
                            const parentItem = this.findItemById(newWorkingItems, parentId);
            
                            if (parentItem) {
                                if (!parentItem.children) parentItem.children = [];
                                targetArray = parentItem.children;
                                movedItem.parent_id = parentItem.id;
                            } else {
                                targetArray = newWorkingItems;
                                movedItem.parent_id = -1;
                            }
                        }
            
                        // Insert at new position
                        targetArray.splice(newIndex, 0, movedItem);
            
                        // Update all order values recursively
                        this.updateOrderRecursively(newWorkingItems);
            
                        // Update the working items
                        this.workingItems = newWorkingItems;
                        this.renderKey++;
            
                        // Re-initialize sortables for the new structure
                        this.$nextTick(() => {
                            this.initializeSortables();
                        });
                    }
                }
            }"
            class=""
            @save-tree-changes.window="saveChanges()"
            @discard-tree-changes.window="discardChanges()"
            @tree-changes-saved.window="refreshTreeItems()">



            <!-- Nested Tree Container -->
            <div class="nested-sortable-tree">
                <div class="nested-sortable" data-level="0">
                    <template x-for="(item, index) in workingItems" :key="`${item.id}-${renderKey}`">
                        <div class="tree-item" :data-id="item.id">
                            <!-- Item Card -->
                            <div class="dark:bg-gray-800 dark:border-gray-700 mb-1 bg-white rounded-lg border border-gray-200 transition-all duration-200">
                                <div class="flex items-stretch">
                                    <div class="drag-handle hover:text-gray-600 dark:hover:text-gray-300 dark:bg-gray-700 dark:border-gray-600 flex justify-center items-center p-1 text-gray-400 bg-gray-50 rounded-l-lg border-r border-gray-200 cursor-move">
                                        <x-filament-nested-sortable::drag-handle class="w-5 h-5" />
                                    </div>
                                    <div class="flex-1 p-3">
                                        <span x-text="item.display" class="dark:text-white text-sm font-medium text-gray-900"></span>
                                        <span class="dark:bg-gray-700 px-2 py-1 ml-2 text-xs text-gray-500 bg-gray-100 rounded">Root Level</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Always show children container if children exist -->
                            <div x-show="(item.children && item.children.length > 0) || isDragging" class="ml-6">
                                <div class="nested-sortable children" :data-level="1">
                                    <template x-for="(child, childIndex) in item.children || []" :key="`${child.id}-${renderKey}`">
                                        <div class="tree-item" :data-id="child.id">
                                            <div class="dark:bg-gray-800 dark:border-gray-700 hover:shadow-md mb-2 bg-white rounded-lg border border-gray-200 shadow-sm transition-all duration-200">
                                                <div class="flex items-stretch">
                                                    <div class="drag-handle hover:text-gray-600 dark:hover:text-gray-300 dark:bg-gray-700 dark:border-gray-600 flex justify-center items-center p-1 text-gray-400 bg-gray-50 rounded-l-lg border-r border-gray-200 cursor-move">
                                                        <x-filament-nested-sortable::drag-handle class="w-5 h-5" />
                                                    </div>
                                                    <div class="flex-1 p-3">
                                                        <span x-text="child.display" class="dark:text-white text-sm font-medium text-gray-900"></span>
                                                        <span class="dark:bg-blue-900 dark:text-blue-300 px-2 py-1 ml-2 text-xs text-blue-700 text-gray-500 bg-blue-100 rounded">Level 1</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Always show level 2 container if children exist -->
                                            <div x-show="(child.children && child.children.length > 0) || isDragging" class="ml-6">
                                                <div class="nested-sortable children" :data-level="2">
                                                    <template x-for="(grandchild, grandchildIndex) in child.children || []" :key="`${grandchild.id}-${renderKey}`">
                                                        <div class="tree-item" :data-id="grandchild.id">
                                                            <div class="dark:bg-gray-800 dark:border-gray-700 hover:shadow-md mb-3 bg-white rounded-lg border border-gray-200 shadow-sm transition-all duration-200">
                                                                <div class="flex items-stretch">
                                                                    <div class="drag-handle hover:text-gray-600 dark:hover:text-gray-300 dark:bg-gray-700 dark:border-gray-600 flex justify-center items-center p-3 text-gray-400 bg-gray-50 rounded-l-lg border-r border-gray-200 cursor-move">
                                                                        <x-filament-nested-sortable::drag-handle class="w-5 h-5" />
                                                                    </div>
                                                                    <div class="flex-1 p-4">
                                                                        <span x-text="grandchild.display" class="dark:text-white text-sm font-medium text-gray-900"></span>
                                                                        <span class="dark:bg-purple-900 dark:text-purple-300 px-2 py-1 ml-2 text-xs text-purple-700 text-gray-500 bg-purple-100 rounded">Level 2</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    @endpush

    @push('styles')
        <style>
            /* Tree structure styling */
            .nested-sortable-tree {
                position: relative;
            }

            .tree-item {
                position: relative;
            }

            /* Children container - transparent by default */
            .nested-sortable.children {
                min-height: 20px;
                transition: all 0.2s ease;
                border-radius: 8px;
                border: 2px dashed transparent;
            }

            /* Only show drop zone styling when dragging AND hovering */
            .is-dragging .nested-sortable.children:hover {
                min-height: 20px;
                padding: 8px;
                margin: 4px 0;
                background: rgba(59, 130, 246, 0.1);
                border: 2px dashed #3b82f6;
            }



            /* Sortable states */
            .sortable-ghost {
                opacity: 0.3;
                background: rgba(59, 130, 246, 0.1) !important;
                border: 2px dashed #3b82f6 !important;
                border-radius: 8px;
                transform: scale(0.95);
                min-height: 60px !important;
                max-height: 60px !important;
                height: 60px !important;
                overflow: hidden;
            }

            .sortable-drag {
                opacity: 0.9;
                transform: rotate(3deg) scale(1.02);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                z-index: 1000;
            }

            .sortable-chosen {
                background: rgba(59, 130, 246, 0.05) !important;
                border-color: #3b82f6 !important;
                transform: scale(1.01);
            }

            /* Enhanced card styling */
            .tree-item .bg-white {
                transition: all 0.2s ease;
            }

            .tree-item:hover .bg-white {
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            /* Dark mode support */
            .dark .tree-item::before,
            .dark .tree-item::after {
                background: #6b7280;
            }

            .dark .is-dragging .nested-sortable.children:hover {
                background: rgba(59, 130, 246, 0.15);
                border-color: #3b82f6;
            }



            .dark .sortable-ghost {
                background: rgba(59, 130, 246, 0.2) !important;
                border-color: #60a5fa !important;
            }

            .dark .sortable-chosen {
                background: rgba(59, 130, 246, 0.1) !important;
                border-color: #3b82f6 !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
