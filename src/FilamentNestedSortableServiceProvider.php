<?php

namespace JohnCarter\FilamentNestedSortable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentNestedSortableServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-nested-sortable';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews();
    }
}
