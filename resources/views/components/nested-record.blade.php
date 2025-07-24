<div
    {{-- Don't add a wire:key here it will break DOM diffing. The key is already added as a Livewire component parameter. --}}
    wire:key="{{ $record->id }}"
    x-sortable-item="{{ $record->id }}"
    x-data="{ collapsed: false }">
    <div>
        <div class="flex bg-white rounded border border-gray-200">
            <div class="flex flex-1 pl-1">
                {{-- Drag handle --}}
                <div x-sortable-handle class="cursor-grab px-1 pt-3.5 pb-3">
                    <x-filament-nested-sortable::drag-handle class="w-4 h-4 text-gray-400" />
                </div>
                {{-- Collapse toggle --}}
                <div
                    class="cursor-pointer px-1 py-3 {{ $record->children->count() > 0 ? 'visible' : 'invisible pointer-events-none' }}"
                    x-on:click="collapsed = !collapsed">
                    <x-filament::icon
                        icon="heroicon-o-chevron-right"
                        class="dark:text-gray-400 w-4 h-4 text-gray-400 transition-transform duration-300"
                        x-cloak
                        x-bind:class="collapsed ? '' : 'rotate-90'" />
                </div>

                <div class="flex flex-1 px-2 py-3 space-x-2">
                    <div class="text-sm font-medium">{{ $record->title }}</div>
                    {{-- 
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> id: {{ $record->id }}</div>
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> order: {{ $record->order }}</div>
                    <div class="p-1 text-xs leading-none text-gray-500 bg-gray-100 border"> parent_id: {{ $record->parent_id }}</div>
                    --}}
                </div>
            </div>
            <div class="px-2 py-3">
                @php
                    // See: https://filamentphp.com/docs/3.x/actions/adding-an-action-to-a-livewire-component#passing-action-arguments
                    // $record passed as $arguments to the Action callback in the NestedSortablePage
                    $actions = [($this->testAction)(['record' => $record])];
                @endphp
                <x-filament-actions::group
                    :actions="$actions"
                    label="Actions"
                    icon="heroicon-m-ellipsis-vertical"
                    color="primary"
                    size="md"
                    tooltip="More actions"
                    dropdown-placement="bottom-start" />
            </div>
        </div>

        {{-- There seem to be a lot of wrapping divs here. But it is required to allow for UI spacing and collapsible nests --}}
        <div class="pt-1 ml-6 rounded transition-all">
            <div
                x-show="!collapsed"
                x-collapse>
                <div
                    x-sortable
                    x-sortable-group="nested-sortable"
                    x-on:end.stop="updateRecordPosition(event)"
                    data-parent-id="{{ $record->id }}">
                    {{-- The children MUST be a DIRECT descendant of the x-sortable element --}}
                    @if ($record->children->count() > 0)
                        @foreach ($record->children as $child)
                            <x-filament-nested-sortable::nested-record :record="$child" :parent-id="$child->parent_id" />
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</div>
