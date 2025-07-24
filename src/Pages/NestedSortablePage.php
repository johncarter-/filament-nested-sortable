<?php

namespace JohnCarter\FilamentNestedSortable\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
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
            ->select('id', 'title', 'slug', 'order', 'parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();
    }

    /* https://filamentphp.com/docs/3.x/actions/adding-an-action-to-a-livewire-component#passing-action-arguments */
    public function testAction(): Action
    {
        return Action::make('test')
            ->icon('heroicon-o-light-bulb')
            ->requiresConfirmation()
            // TODO: Allow for dynamic record injection
            ->action(function (array $arguments) {
                ray($arguments['record'])->label('testAction');
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $this->getResource()::getModel()::find($arguments['record']['id'])->delete();
                $this->records = $this->getRecords();
            });
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create new record')
                ->form([
                    TextInput::make('title')
                        ->label('Title')
                        ->required(),
                ])
                ->modalWidth('md')
                ->action(function (array $data) {
                    $this->createRecord($data);
                }),
        ];
    }

    public function createRecord(array $data): void
    {
        $this->getResource()::getModel()::create(
            array_merge(
                $data,
                ['slug' => \Illuminate\Support\Str::slug($data['title'])]
            )
        );

        $this->records = $this->getRecords();
    }

    #[Renderless]
    public function persistRecordUpdates($pendingRecordUpdates)
    {
        foreach ($pendingRecordUpdates as $update) {
            $record = $this->getResource()::getModel()::find($update['id']);
            $record->update($update);
        }

        $this->records = $this->getRecords();

        $this->dispatch('reset-pending-record-updates');
    }
}
