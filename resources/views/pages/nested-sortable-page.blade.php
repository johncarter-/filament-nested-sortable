<x-filament-panels::page>
    <div
        x-sortable
        x-on:end="console.log('Sorting ended:', $event)">
        @foreach ($records as $record)
            {{-- Must be @livewire else Alpine tries to bind to the item attribute  --}}
            {{-- Not working: <livewire:filament-nested-sortable::nested-item :item="$item" /> --}}
            @livewire('filament-nested-sortable::nested-record', ['record' => $record])
        @endforeach
    </div>
</x-filament-panels::page>
