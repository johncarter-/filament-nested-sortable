<?php

namespace JohnCarter\FilamentNestedSortable;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentNestedSortablePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-nested-sortable';
    }

    public function register(Panel $panel): void
    {
        // Plugin registration logic can go here
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
