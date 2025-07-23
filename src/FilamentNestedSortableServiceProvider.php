<?php

namespace JohnCarter\FilamentNestedSortable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentNestedSortableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-nested-sortable')
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        // Register the view namespace
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-nested-sortable');

        // Register the language files
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'filament-nested-sortable');

        // Register Livewire components
        $this->loadLivewireComponents();
    }

    protected function loadLivewireComponents(): void
    {
        \Livewire\Livewire::component('filament-nested-sortable::nested-record', \JohnCarter\FilamentNestedSortable\Livewire\NestedRecord::class);
    }
}
