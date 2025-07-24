<x-filament-panels::page>
    <div
        {{-- Using Filament Alpine directive of SortableJS --}}
        {{-- https://github.com/filamentphp/filament/blob/3.x/packages/support/resources/js/sortable.js --}}
        x-sortable
        x-sortable-group="nested-sortable"
        x-on:end.stop="updateFullNest($event)"
        x-ref="rootNestedSortable"
        x-data="{
            updateFullNest($event) {
                console.log('updating full nest');
                // Get all the nested `sorted` instances registered within this x-sortable element
        
                // Scan the DOM for all the `sorted` instances registered within this x-sortable element
                const sortedInstances = $refs.rootNestedSortable.sortable;
                console.log('Instances:');
                console.log(sortedInstances);
        
                $wire.reorderNest(sortedInstances);
            }
        }">
        @foreach ($records as $record)
            {{-- Only show root level records, the children are rendered by the nested-record component --}}
            @if ($record->parent_id == -1)
                <x-filament-nested-sortable::nested-record :record="$record" />
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
