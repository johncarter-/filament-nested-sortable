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
            ->hasViews();
    }

    public function packageBooted(): void
    {
        // Register the view namespace
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-nested-sortable');
    }
}
