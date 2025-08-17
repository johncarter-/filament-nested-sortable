<?php

namespace JohnCarter\FilamentNestedSortable\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Renderless;

abstract class NestedSortablePage extends Page
{
    public EloquentCollection $records;

    public string $recordKeyName = 'id';

    public string $parentColumn = 'parent_id';

    public string $orderColumn = 'order';

    public string $childrenRelationName = 'children';

    protected string $view = 'filament-nested-sortable::pages.nested-sortable-page';

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
            ->with($this->childrenRelationName)
            ->orderBy($this->orderColumn)
            ->get();
    }

    public function getRecordLabelColumn(): string
    {
        return 'title';
    }

    public function hasRecordUrl(): bool
    {
        return true;
    }

    public function getRecordUrl($record): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $record[$this->recordKeyName]]);
    }

    public function getRecordLabel($record): Htmlable | string
    {
        if ($this->hasRecordUrl()) {
            return new HtmlString(
                '<a href="' . $this->getRecordUrl($record) . '">' . $record->{$this->getRecordLabelColumn()} . '</a>'
            );
        }

        return $record->{$this->getRecordLabelColumn()};
    }

    public function getRecordActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->icon('heroicon-o-pencil')
            ->url(function (array $arguments) {
                return $this->getResource()::getUrl('edit', ['record' => $arguments['record'][$this->recordKeyName]]);
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
                $this->getResource()::getModel()::find($arguments['record'][$this->recordKeyName])->delete();
                $this->records = $this->getRecords();
            });
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create new record')
                ->schema($this->getCreateRecordFormSchema())
                ->modalWidth('md')
                ->action(function (array $data) {
                    $this->createRecord($data);
                }),
        ];
    }

    public function getCreateRecordFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required(),
        ];
    }

    public function createRecord(array $data): void
    {
        $this->getResource()::getModel()::create($data);

        $this->records = $this->getRecords();
    }

    #[Renderless]
    public function persistRecordUpdates($pendingRecordUpdates)
    {
        foreach ($pendingRecordUpdates as $update) {
            $record = $this->getResource()::getModel()::find($update[$this->recordKeyName]);
            $record->update($update);
        }

        $this->records = $this->getRecords();

        $this->dispatch('reset-pending-record-updates');
    }
}
