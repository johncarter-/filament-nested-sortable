<x-filament-panels::page>
    <script>
        window.treeData = @json($this->getTreeItems());

        document.addEventListener('alpine:init', () => {
            Alpine.data('treeComponent', () => ({
                items: window.treeData,
                originalItems: window.treeData,
                workingItems: window.treeData,
                hasChanges: false,
                dragging: null,
                draggedOver: null,
                dropPosition: null,
                dragOverTimeout: null,
                displayItems: [],

                init() {
                    this.displayItems = this.flattenItems(this.workingItems);

                    // Global event listeners for cleanup
                    document.addEventListener('dragend', () => this.clearDragState());
                    document.addEventListener('drop', () => this.clearDragState());
                },

                flattenItems(items, depth = 0) {
                    let flat = [];
                    items.forEach(item => {
                        const itemWithDepth = {
                            ...item,
                            depth: depth,
                            indent: depth * 24
                        };
                        flat.push(itemWithDepth);
                        if (item.children && item.children.length > 0) {
                            flat.push(...this.flattenItems(item.children, depth + 1));
                        }
                    });
                    return flat;
                },

                startDrag(event, item) {
                    const rect = event.currentTarget.getBoundingClientRect();
                    this.dragging = {
                        ...item,
                        height: rect.height
                    };
                    event.dataTransfer.effectAllowed = 'move';
                },

                clearDragState() {
                    this.dragging = null;
                    this.draggedOver = null;
                    this.dropPosition = null;
                    if (this.dragOverTimeout) {
                        clearTimeout(this.dragOverTimeout);
                        this.dragOverTimeout = null;
                    }
                },

                dragOverZone(event, item, position) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    this.draggedOver = item;
                    this.dropPosition = position;
                },

                dragOverItem(event, item) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';

                    const rect = event.currentTarget.getBoundingClientRect();
                    const relativeY = (event.clientY - rect.top) / rect.height;

                    this.draggedOver = item;

                    if (relativeY < 0.25) {
                        this.dropPosition = 'before';
                    } else if (relativeY > 0.75) {
                        this.dropPosition = 'after';
                    } else {
                        this.dropPosition = 'nest';
                    }

                    // Debug logging for child items
                    if (item.depth > 0) {
                        console.log(`Hovering over child item: ${item.display} (depth: ${item.depth}), position: ${this.dropPosition}`);
                    }
                },

                dragLeaveZone(event) {
                    if (this.dragOverTimeout) {
                        clearTimeout(this.dragOverTimeout);
                    }

                    this.dragOverTimeout = setTimeout(() => {
                        if (!event.relatedTarget || !event.relatedTarget.closest('[draggable="true"], [x-on\\:dragover]')) {
                            this.draggedOver = null;
                            this.dropPosition = null;
                        }
                    }, 50);
                },

                dropInZone(event, targetItem, position) {
                    event.preventDefault();
                    this.handleDrop(targetItem, position);
                },

                dropOnItem(event, targetItem) {
                    event.preventDefault();
                    this.handleDrop(targetItem, this.dropPosition);
                },

                handleDrop(targetItem, position) {
                    if (!this.dragging || this.dragging.id === targetItem.id) {
                        this.clearDragState();
                        return;
                    }

                    // Prevent circular references - don't allow dragging a parent into its own child
                    if (position === 'nest' && this.wouldCreateCircularReference(this.dragging.id, targetItem.id)) {
                        console.log('Preventing circular reference');
                        this.clearDragState();
                        return;
                    }

                    if (position === 'nest') {
                        this.nestItem(this.dragging.id, targetItem.id);
                    } else {
                        this.reorderItem(this.dragging.id, targetItem.id, position);
                    }

                    this.clearDragState();
                },

                wouldCreateCircularReference(draggedItemId, targetItemId) {
                    // Check if targetItem is a descendant of draggedItem
                    const isDescendant = this.isDescendantOf(draggedItemId, targetItemId);
                    return isDescendant;
                },

                isDescendantOf(parentId, childId) {
                    const parent = this.findItemInTree(this.workingItems, parentId);
                    if (!parent || !parent.children) return false;

                    for (let child of parent.children) {
                        if (child.id === childId) return true;
                        if (this.isDescendantOf(child.id, childId)) return true;
                    }
                    return false;
                },

                nestItem(draggedItemId, targetItemId) {
                    const draggedItem = this.removeItemFromTree(this.workingItems, draggedItemId);
                    if (!draggedItem) return;

                    const targetItem = this.findItemInTree(this.workingItems, targetItemId);
                    if (!targetItem) return;

                    if (!targetItem.children) {
                        targetItem.children = [];
                    }
                    targetItem.children.push(draggedItem);

                    this.hasChanges = true;
                    this.$wire.call('setHasChanges', true);
                    this.displayItems = this.flattenItems(this.workingItems);
                },

                reorderItem(draggedItemId, targetItemId, position) {
                    const draggedItem = this.removeItemFromTree(this.workingItems, draggedItemId);
                    if (!draggedItem) return;

                    const targetInfo = this.findItemWithParent(this.workingItems, targetItemId);
                    if (!targetInfo) return;

                    let insertIntoArray, insertPosition;

                    if (targetInfo.parent) {
                        insertIntoArray = targetInfo.parent.children;
                        insertPosition = insertIntoArray.findIndex(item => item.id === targetItemId);
                    } else {
                        insertIntoArray = this.workingItems;
                        insertPosition = insertIntoArray.findIndex(item => item.id === targetItemId);
                    }

                    if (position === 'after') {
                        insertPosition++;
                    }

                    insertIntoArray.splice(insertPosition, 0, draggedItem);

                    this.hasChanges = true;
                    this.$wire.call('setHasChanges', true);
                    this.displayItems = this.flattenItems(this.workingItems);
                },

                removeItemFromTree(items, itemId) {
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].id === itemId) {
                            return items.splice(i, 1)[0];
                        }
                        if (items[i].children) {
                            const found = this.removeItemFromTree(items[i].children, itemId);
                            if (found) return found;
                        }
                    }
                    return null;
                },

                findItemInTree(items, itemId) {
                    for (let item of items) {
                        if (item.id === itemId) {
                            return item;
                        }
                        if (item.children) {
                            const found = this.findItemInTree(item.children, itemId);
                            if (found) return found;
                        }
                    }
                    return null;
                },

                findItemWithParent(items, itemId, parent = null) {
                    for (let item of items) {
                        if (item.id === itemId) {
                            return {
                                item,
                                parent
                            };
                        }
                        if (item.children) {
                            const found = this.findItemWithParent(item.children, itemId, item);
                            if (found) return found;
                        }
                    }
                    return null;
                },

                saveChanges() {
                    const flattenedForSave = this.flattenForSave(this.workingItems);
                    this.$wire.call('persistTreeChanges', flattenedForSave);
                },

                discardChanges() {
                    this.workingItems = JSON.parse(JSON.stringify(this.originalItems));
                    this.displayItems = this.flattenItems(this.workingItems);
                    this.hasChanges = false;
                    this.$wire.call('setHasChanges', false);
                },

                flattenForSave(items, parentId = null, currentOrder = 0) {
                    let flattened = [];
                    items.forEach((item, index) => {
                        flattened.push({
                            id: item.id,
                            parent_id: parentId,
                            order: currentOrder + index
                        });
                        if (item.children && item.children.length > 0) {
                            const childrenFlattened = this.flattenForSave(item.children, item.id, 0);
                            flattened.push(...childrenFlattened);
                        }
                    });
                    return flattened;
                },

                onTreeSaved() {
                    this.originalItems = JSON.parse(JSON.stringify(this.workingItems));
                    this.hasChanges = false;
                    this.$wire.call('setHasChanges', false);
                }
            }));
        });
    </script>

    <div
        x-data="treeComponent"
        x-on:save-tree-changes.window="saveChanges()"
        x-on:discard-tree-changes.window="discardChanges()"
        x-on:tree-changes-saved.window="onTreeSaved()">

        <div class="space-y-0">
            <template x-for="(item, index) in displayItems" x-bind:key="item.id">
                <div>
                    <!-- Actual Item -->
                    <div
                        draggable="true"
                        x-on:dragstart="startDrag($event, item)"
                        x-on:dragover="dragOverItem($event, item)"
                        x-on:dragleave="dragLeaveZone($event)"
                        x-on:drop="dropOnItem($event, item)"
                        x-on:dragend="clearDragState()"
                        x-bind:class="{
                            'opacity-30': dragging?.id === item.id,
                            'ring-2 ring-blue-300 bg-blue-50': draggedOver?.id === item.id && dropPosition === 'nest',
                            'border-t-4 border-t-blue-500': draggedOver?.id === item.id && dropPosition === 'before',
                            'border-b-4 border-b-blue-500': draggedOver?.id === item.id && dropPosition === 'after',
                            'bg-gray-50': item.depth > 0,
                            'bg-white': item.depth === 0 || !item.depth
                        }"
                        class="hover:shadow-md relative rounded-lg border border-gray-200 shadow-sm transition-all duration-100 cursor-move"
                        x-bind:style="(draggedOver?.id === item.id && dropPosition === 'nest' ? 'transform: scale(1.02);' : '') + 'margin-left: ' + (item.indent || 0) + 'px;'">

                        <div class="flex items-center p-3 bg-white rounded-lg">
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
                                <span class="px-2 py-1 ml-2 text-xs text-gray-500 bg-gray-100 rounded" x-text="`ID: ${item.id}`"></span>
                                <span x-show="item.depth > 0" class="px-2 py-1 ml-2 text-xs text-indigo-600 bg-indigo-100 rounded" x-text="`Level ${item.depth}`"></span>

                                <!-- Nesting indicator -->
                                <span x-show="draggedOver?.id === item.id && dropPosition === 'nest'"
                                    class="px-2 py-1 ml-2 text-xs font-medium text-green-700 bg-green-100 rounded border border-green-300">
                                    📁 Add as child<span x-show="item.depth > 0" class="ml-1" x-text="`(Level ${item.depth + 1})`"></span>
                                </span>

                                <!-- Reordering indicators -->
                                <span x-show="draggedOver?.id === item.id && dropPosition === 'before'"
                                    class="px-2 py-1 ml-2 text-xs font-medium text-blue-600 bg-blue-100 rounded">
                                    ↑ Insert above<span x-show="item.depth === 0" class="ml-1 font-bold">(Root Level)</span>
                                </span>
                                <span x-show="draggedOver?.id === item.id && dropPosition === 'after'"
                                    class="px-2 py-1 ml-2 text-xs font-medium text-blue-600 bg-blue-100 rounded">
                                    ↓ Insert below<span x-show="item.depth === 0" class="ml-1 font-bold">(Root Level)</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Drop Zone AFTER every item -->
                    <div x-on:dragover="dragOverZone($event, item, 'after')"
                        x-on:dragleave="dragLeaveZone($event)"
                        x-on:drop="dropInZone($event, item, 'after')"
                        x-bind:class="{
                            'h-12 bg-blue-50 border-2 border-dashed border-blue-300 rounded-lg': draggedOver?.id === item.id && dropPosition === 'after',
                            'h-0': !(draggedOver?.id === item.id && dropPosition === 'after')
                        }"
                        class="flex justify-center items-center transition-all duration-200 ease-out"
                        x-bind:style="'margin-left: ' + (item.indent || 0) + 'px;'">

                        <!-- Ghost element for AFTER position -->
                        <div x-show="draggedOver?.id === item.id && dropPosition === 'after' && dragging"
                            class="w-full bg-white rounded-lg border-2 border-blue-400 shadow-lg opacity-75"
                            x-bind:style="{ height: dragging?.height + 'px', marginLeft: (item.indent || 0) + 'px' }">
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
                                    <span class="px-2 py-1 ml-2 text-xs text-blue-600 bg-blue-100 rounded">Drop here</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <style>
            * {
                list-style: none !important;
            }
        </style>
    </div>
</x-filament-panels::page>
