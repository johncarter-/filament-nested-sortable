<?php

namespace JohnCarter\FilamentNestedSortable\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Renderless;

abstract class NestedSortablePage extends Page
{
    public EloquentCollection $records;

    protected static string $view = 'filament-nested-sortable::pages.nested-sortable-page';

    public function mount(): void
    {
        $this->records = $this->getRecords();
    }

    public function getTitle(): string
    {
        return $this->getResource()::getBreadcrumb();
    }

    public function getRecords(): EloquentCollection
    {
        return $this->getResource()::getEloquentQuery()
            ->select('id', 'title', 'order', 'parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();
    }

    /* https://filamentphp.com/docs/3.x/actions/adding-an-action-to-a-livewire-component#passing-action-arguments */
    public function testAction(): Action
    {
        return Action::make('test')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                ray($arguments['record'])->label('testAction');
            });
    }

    #[Renderless]
    public function reorderNest(array $sortedRecords): void
    {
        ray($sortedRecords)->label('NestedSortablePage');
    }
}
