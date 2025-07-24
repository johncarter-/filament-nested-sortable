<div
    {{-- Don't add a wire:key here it will break DOM diffing. The key is already added as a Livewire component parameter. --}}
    wire:key="{{ $record->id }}"
    x-sortable-item="{{ $record->id }}"
    data-item-id="{{ $record->id }}"
    x-data="{
        hasChildren: {{ $record->children->count() > 0 ? 'true' : 'false' }},
        collapsed: false,
        checkHasChildren() {
            // Check if the child [x-sortable] has x-sortable-items in it
            const childSortable = this.$el.querySelector('[x-sortable]');
            this.hasChildren = childSortable.querySelectorAll('[x-sortable-item]').length > 0;
        }
    }"
    x-on:updated-record-position.window="checkHasChildren()">
    <div>
        <div class="flex bg-white rounded border border-gray-200">
            <div class="flex flex-1 pl-1">
                {{-- Drag handle --}}
                <div x-sortable-handle class="cursor-grab px-1 pt-3.5 pb-3">
                    <x-filament-nested-sortable::drag-handle class="w-4 h-4 text-gray-400" />
                </div>
                {{-- Collapse toggle --}}
                <div
                    x-bind:class="hasChildren ? 'visible' : 'invisible pointer-events-none'"
                    class="px-1 pt-3.5 pb-3"
                    x-on:click="collapsed = !collapsed">
                    <x-filament::icon
                        icon="heroicon-o-chevron-right"
                        class="dark:text-gray-400 w-4 h-4 text-gray-400 transition-transform duration-300"
                        x-cloak
                        x-bind:class="collapsed ? '' : 'rotate-90'" />
                </div>

                <div class="flex flex-1 items-center px-2 py-3 space-x-2">
                    <div class="text-sm font-medium">{{ $record->title }}</div>
                    @if ($record->slug)
                        <div class="font-mono text-sm text-gray-400">{{ $record->slug }}</div>
                    @endif
                    {{-- 
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> id: {{ $record->id }}</div>
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> order: {{ $record->order }}</div>
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> parent_id: {{ $record->parent_id }}</div>
                    --}}
                </div>
            </div>
            <div
                {{-- TODO: Using actions when there are pending changes breaks DOM --}}
                x-bind:class="pendingRecordUpdates.length === 0 ? '' : 'opacity-30 pointer-events-none'"
                class="px-2 py-3">
                @php
                    // See: https://filamentphp.com/docs/3.x/actions/adding-an-action-to-a-livewire-component#passing-action-arguments
                    // $record passed as $arguments to the Action callback in the NestedSortablePage
                    $actions = [($this->testAction)(['record' => $record]), ($this->deleteAction)(['record' => $record])];
                @endphp
                <x-filament-actions::group
                    :actions="$actions"
                    label="Actions"
                    icon="heroicon-m-ellipsis-vertical"
                    color="primary"
                    size="sm"
                    dropdown-placement="bottom-start" />
            </div>
        </div>

        {{-- There seem to be a lot of wrapping divs here. But it is required to allow for UI spacing and collapsible nests --}}
        <div class="pt-1 ml-6 rounded transition-all">
            <div
                x-show="!collapsed"
                x-collapse>
                <div
                    {{-- We dont need an x-on:end (SortableJS) event here as the parent will handle it --}}
                    x-sortable
                    x-sortable-group="nested-sortable"
                    data-parent-id="{{ $record->id }}">
                    {{-- The children MUST be a DIRECT descendant of the x-sortable element --}}
                    @if ($record->children->count() > 0)
                        @foreach ($record->children as $child)
                            <x-filament-nested-sortable::nested-record :record="$child" />
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
