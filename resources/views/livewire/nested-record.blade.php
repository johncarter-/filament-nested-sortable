<div
    wire:key="{{ $record->id }}"
    x-sortable-item="{{ $record->id }}"
    x-data="{ collapsed: false }">
    <div>
        <div class="flex bg-white rounded border border-gray-200">
            <div class="flex flex-1 pl-1">
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
                {{-- Drga handle --}}
                <div x-sortable-handle class="cursor-grab px-1 py-3">
                    <x-filament-nested-sortable::drag-handle class="w-4 h-4 text-gray-400" />
                </div>
                <div class="flex-1 px-2 py-3">
                    <div class="text-sm font-medium">{{ $record->title }}</div>
                </div>
            </div>
            <div class="px-2 py-3">
                {{ $this->getActionGroup() }}
            </div>
        </div>

        <div
            class="pt-1 ml-6 rounded transition-all"
            x-sortable
            x-sortable-group="nested-sortable"
            x-on:end="console.log('Sorting ended:', $event)"
            x-on:start="console.log('Sorting started:', $event)">
            <div x-show="!collapsed" x-collapse>
                @if ($record->children->count() > 0)
                    @foreach ($record->children as $child)
                        @livewire('filament-nested-sortable::nested-record', ['record' => $child, 'parentId' => $child->parent_id], key($child->id))
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</div>
