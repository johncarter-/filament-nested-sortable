<x-filament-panels::page>
    <div class="">
        <div
            x-data="{
                items: {{ Js::from($this->getTreeItems()) }},
                originalItems: {{ Js::from($this->getTreeItems()) }},
                workingItems: {{ Js::from($this->getTreeItems()) }},
                hasChanges: false,
                dragging: null,
                draggedOver: null,
                dropPosition: null, // 'before', 'after', or null
                dragOverTimeout: null, // For cleanup
                displayItems: [], // Stage 1: Separate array for flat display
            
                init() {
                    this.$watch('workingItems', (newItems) => {
                        this.checkForChanges();
                    }, { deep: true });
            
                    // Initialize display items for Stage 1
                    this.displayItems = this.flattenItems(this.workingItems);
                },
            
                // Flatten tree for simple single-level testing
                flattenItems(items) {
                    const flatten = (items) => {
                        let flat = [];
                        items.forEach(item => {
                            flat.push(item);
                            if (item.children && item.children.length > 0) {
                                flat.push(...flatten(item.children));
                            }
                        });
                        return flat;
                    };
                    return flatten(items);
                },
            
                startDrag(event, item) {
                    // Capture the height of the dragged element
                    const rect = event.currentTarget.getBoundingClientRect();
                    this.dragging = {
                        ...item,
                        height: rect.height
                    };
            
                    event.dataTransfer.effectAllowed = 'move';
                    console.log('Starting drag:', this.dragging);
            
                    // Also set up cleanup on dragend (in case drop doesn't fire)
                    event.target.addEventListener('dragend', () => {
                        this.clearDragState();
                    }, { once: true });
                },
            
                clearDragState() {
                    this.dragging = null;
                    this.draggedOver = null;
                    this.dropPosition = null;
                    if (this.dragOverTimeout) {
                        clearTimeout(this.dragOverTimeout);
                        this.dragOverTimeout = null;
                    }
                    console.log('Drag state cleared');
                },
            
                dragOverZone(event, item, position) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
            
                    // Set the targeted drop zone
                    this.draggedOver = item;
                    this.dropPosition = position;
                },
            
                dragLeaveZone(event) {
                    // Clear state when leaving drop zone
                    if (!event.currentTarget.contains(event.relatedTarget)) {
                        this.draggedOver = null;
                        this.dropPosition = null;
                    }
                },
            
                dropInZone(event, targetItem, position) {
                    event.preventDefault();
            
                    if (!this.dragging || this.dragging.id === targetItem.id) {
                        this.clearDragState();
                        return;
                    }
            
                    console.log('Dropping', this.dragging, 'at position:', position, 'relative to:', targetItem);
            
                    // Find indices - use the original item for array operations
                    const dragIndex = this.displayItems.findIndex(item => item.id === this.dragging.id);
                    let targetIndex = this.displayItems.findIndex(item => item.id === targetItem.id);
            
                    if (dragIndex !== -1 && targetIndex !== -1) {
                        // Remove dragged item first (use the original item from the array)
                        const [draggedItem] = this.displayItems.splice(dragIndex, 1);
            
                        // Adjust target index if we removed item before it
                        if (dragIndex < targetIndex) {
                            targetIndex -= 1;
                        }
            
                        // Insert based on position
                        const insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
                        this.displayItems.splice(insertIndex, 0, draggedItem);
            
                        console.log('Reordered:', this.displayItems.map(i => i.display));
                        console.log('Inserted at position:', insertIndex);
                    }
            
                    // Clear all drag states
                    this.clearDragState();
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
            
                            onChoose: (evt) => {
                                // Capture original dimensions and positioning before sortable-chosen class is applied
                                const originalHeight = evt.item.offsetHeight;
                                const originalWidth = evt.item.offsetWidth;
                                const computedStyle = window.getComputedStyle(evt.item);
                                const rect = evt.item.getBoundingClientRect();
            
                                // Capture all relevant positioning properties
                                evt.item.style.setProperty('--original-height', originalHeight + 'px');
                                evt.item.style.setProperty('--original-width', originalWidth + 'px');
                                evt.item.style.setProperty('--original-margin-left', computedStyle.marginLeft);
                                evt.item.style.setProperty('--original-margin-right', computedStyle.marginRight);
                                evt.item.style.setProperty('--original-margin-top', computedStyle.marginTop);
                                evt.item.style.setProperty('--original-margin-bottom', computedStyle.marginBottom);
                                evt.item.style.setProperty('--original-padding-left', computedStyle.paddingLeft);
                                evt.item.style.setProperty('--original-padding-right', computedStyle.paddingRight);
            
            
                            },
            
                            onStart: (evt) => {
                                this.isDragging = true;
                                document.body.classList.add('is-dragging');
                            },
            
                            onEnd: (evt) => {
                                // Clean up all custom properties
                                evt.item.style.removeProperty('--original-height');
                                evt.item.style.removeProperty('--original-width');
                                evt.item.style.removeProperty('--original-margin-left');
                                evt.item.style.removeProperty('--original-margin-right');
                                evt.item.style.removeProperty('--original-margin-top');
                                evt.item.style.removeProperty('--original-margin-bottom');
                                evt.item.style.removeProperty('--original-padding-left');
                                evt.item.style.removeProperty('--original-padding-right');
            
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



            <!-- STAGE 1: Simple Single-Level Drag & Drop -->
            <div class="bg-yellow-100 border border-yellow-400 rounded p-4 mb-4">
                <h4 class="font-bold text-yellow-800 mb-2">🚧 Stage 1: Basic Drag & Drop Testing</h4>
                <p class="text-yellow-700 text-sm">This shows all items in a flat list for basic drag/drop testing. Check console for debug info.</p>

            </div>

            <div class="space-y-0">
                <template x-for="(item, index) in displayItems" :key="item.id">
                    <div>
                        <!-- Drop Zone BEFORE first item only -->
                        <div x-show="index === 0"
                            @dragover="dragOverZone($event, item, 'before')"
                            @dragleave="dragLeaveZone($event)"
                            @drop="dropInZone($event, item, 'before')"
                            :class="{
                                'h-12 bg-blue-50 border-2 border-dashed border-blue-300 rounded-lg': draggedOver?.id === item.id && dropPosition === 'before',
                                'h-3 bg-gray-50 border border-dashed border-gray-300 rounded opacity-50': dragging && !(draggedOver?.id === item.id && dropPosition === 'before'),
                                'h-0': !dragging
                            }"
                            class="transition-all duration-200 ease-out flex items-center justify-center">

                            <!-- Ghost element for BEFORE position -->
                            <div x-show="draggedOver?.id === item.id && dropPosition === 'before' && dragging"
                                class="w-full bg-white border-2 border-blue-400 rounded-lg shadow-lg opacity-75"
                                :style="{ height: dragging?.height + 'px' }">
                                <div class="flex items-center p-3">
                                    <div class="flex-shrink-0 mr-3">
                                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="7" cy="5" r="1.5"></circle>
                                            <circle cx="13" cy="5" r="1.5"></circle>
                                            <circle cx="7" cy="10" r="1.5"></circle>
                                            <circle cx="13" cy="10" r="1.5"></circle>
                                            <circle cx="7" cy="15" r="1.5"></circle>
                                            <circle cx="13" cy="15" r="1.5"></circle>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <span x-text="dragging?.display" class="text-sm font-medium text-blue-700"></span>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded">Drop here</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actual Item -->
                        <div
                            draggable="true"
                            @dragstart="startDrag($event, item)"
                            :class="{
                                'opacity-30': dragging?.id === item.id,
                                'ring-2 ring-blue-300': draggedOver?.id === item.id && !dropPosition
                            }"
                            class="bg-white rounded-lg border border-gray-200 shadow-sm transition-all duration-100 cursor-move hover:shadow-md">

                            <div class="flex items-center p-3">
                                <div class="flex-shrink-0 mr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="7" cy="5" r="1.5"></circle>
                                        <circle cx="13" cy="5" r="1.5"></circle>
                                        <circle cx="7" cy="10" r="1.5"></circle>
                                        <circle cx="13" cy="10" r="1.5"></circle>
                                        <circle cx="7" cy="15" r="1.5"></circle>
                                        <circle cx="13" cy="15" r="1.5"></circle>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <span x-text="item.display" class="text-sm font-medium text-gray-900"></span>
                                    <span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded" x-text="`ID: ${item.id}`"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Drop Zone AFTER every item -->
                        <div @dragover="dragOverZone($event, item, 'after')"
                            @dragleave="dragLeaveZone($event)"
                            @drop="dropInZone($event, item, 'after')"
                            :class="{
                                'h-12 bg-blue-50 border-2 border-dashed border-blue-300 rounded-lg': draggedOver?.id === item.id && dropPosition === 'after',
                                'h-3 bg-gray-50 border border-dashed border-gray-300 rounded opacity-50': dragging && !(draggedOver?.id === item.id && dropPosition === 'after'),
                                'h-0': !dragging
                            }"
                            class="transition-all duration-200 ease-out flex items-center justify-center">

                            <!-- Ghost element for AFTER position -->
                            <div x-show="draggedOver?.id === item.id && dropPosition === 'after' && dragging"
                                class="w-full bg-white border-2 border-blue-400 rounded-lg shadow-lg opacity-75"
                                :style="{ height: dragging?.height + 'px' }">
                                <div class="flex items-center p-3">
                                    <div class="flex-shrink-0 mr-3">
                                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="7" cy="5" r="1.5"></circle>
                                            <circle cx="13" cy="5" r="1.5"></circle>
                                            <circle cx="7" cy="10" r="1.5"></circle>
                                            <circle cx="13" cy="10" r="1.5"></circle>
                                            <circle cx="7" cy="15" r="1.5"></circle>
                                            <circle cx="13" cy="15" r="1.5"></circle>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <span x-text="dragging?.display" class="text-sm font-medium text-blue-700"></span>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded">Drop here</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>



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
                padding: 4px;
                margin: 1px 0;
                background: rgba(59, 130, 246, 0.1);
                border: 2px dashed #3b82f6;
            }



            /* Sortable states */
            .sortable-ghost {
                opacity: 0.9;
                background: rgba(59, 130, 246, 0.1) !important;
                border: 2px dashed #3b82f6 !important;
                border-radius: 8px;
                transform: scale(0.95);
                min-height: 60px !important;
                max-height: 60px !important;
                height: 60px !important;
                overflow: hidden;
                margin-left: calc(var(--original-margin-left, 0px) + 24px) !important;
                box-sizing: border-box !important;
            }

            .sortable-drag {
                opacity: 0.9;
                transform: rotate(3deg) scale(1.02);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                z-index: 1000;
            }

            .sortable-chosen {
                background: rgba(59, 130, 246, 0.05) !important;
                border: 1px dashed red !important;
                transform: scale(1.01);
                height: var(--original-height) !important;
                width: var(--original-width) !important;
                max-height: var(--original-height) !important;
                min-height: var(--original-height) !important;
                max-width: var(--original-width) !important;
                min-width: var(--original-width) !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                margin: var(--original-margin-top) var(--original-margin-right) var(--original-margin-bottom) var(--original-margin-left) !important;
                padding-left: var(--original-padding-left) !important;
                padding-right: var(--original-padding-right) !important;
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
                padding: 4px;
                margin: 1px 0;
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
