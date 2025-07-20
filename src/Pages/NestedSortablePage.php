<?php

namespace JohnCarter\FilamentNestedSortable\Pages;

use Filament\Pages\Page;

class NestedSortablePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string $view = 'filament-nested-sortable::pages.nested-sortable-page';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?string $title = 'Nested Sortable';

    protected static ?string $slug = 'nested-sortable';
}
