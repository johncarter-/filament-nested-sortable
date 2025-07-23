<x-filament-panels::page>
    <div
        {{-- Using Filament Alpine directive of SortableJS --}}
        {{-- https://github.com/filamentphp/filament/blob/3.x/packages/support/resources/js/sortable.js --}}
        x-sortable
        x-sortable-group="nested-sortable"
        x-on:end.stop="$wire.reorderTable($event.target.sortable.toArray())">
        @foreach ($records as $record)
            {{-- Must be @livewire syntax else Alpine tries to bind to the `record` attribute  --}}
            {{-- Not working: <livewire:filament-nested-sortable::nested-record :record="$record" /> --}}

            {{-- We must use a Livewire component here so we can use the Filament Action group else we have problems with the modals --}}
            {{-- Only show root level records, the children are rendered by the nested-record component --}}

            @if ($record->parent_id == -1)
                @livewire('filament-nested-sortable::nested-record', ['record' => $record], key($record->id))
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
