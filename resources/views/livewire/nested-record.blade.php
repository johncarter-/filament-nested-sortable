<div
    x-sort:item
    x-sortable-item="{{ $record->id }}">
    <div class="flex bg-white rounded border border-gray-200">
        <div class="flex flex-1">
            <div x-sortable-handle class="cursor-grab px-2 py-3">
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
    <x-filament-actions::modals />
</div>
