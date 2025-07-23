<?php

namespace JohnCarter\FilamentNestedSortable\Livewire;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;

class NestedRecord extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public $record;

    public function getActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->testAction(),
        ]);
    }

    public function testAction(): Action
    {
        return Action::make('test')
            ->requiresConfirmation()
            ->action(fn() => ray($this->record->id));
    }

    public function render()
    {
        return view('filament-nested-sortable::livewire.nested-record');
    }
}
