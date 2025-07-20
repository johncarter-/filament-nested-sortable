# Filament Nested Sortable Plugin

A Filament 3 plugin for ordering and nesting model records based on `parent_id` and `order` columns.

## Structure

This is a minimal scaffold with:

- `src/FilamentNestedSortableServiceProvider.php` - Main service provider
- `src/Pages/NestedSortablePage.php` - Empty Filament page
- `resources/views/pages/nested-sortable-page.blade.php` - Blade view with placeholder for Alpine.js component

## Usage

The plugin creates a new page in Filament admin at `/admin/nested-sortable` with a placeholder for the Alpine.js x-sort component.

## Development

This is currently a local package scaffolded in `local_packages/`. To make it a standalone repository, you would:

1. Move it to its own repository
2. Update the composer.json with proper dependencies
3. Implement the actual Alpine.js x-sort functionality
4. Add proper API endpoints for data management

## Tailwind CSS Configuration

Since this is a local package, Tailwind classes used in the plugin views need to be included in your Filament CSS build. Add the plugin's view paths to your `resources/css/filament/cp/theme.css` file:

```css
@import '../../../../local_packages/filament-nested-sortable/resources/views/**/*.blade.php';
```

Or alternatively, add the plugin path to your Filament-specific Tailwind config (if you have one) or include the classes directly in your Filament CSS file.

After updating the CSS, rebuild your Filament assets:

```bash
php artisan filament:upgrade
```

This ensures the plugin's Tailwind classes are included in the Filament admin panel's CSS build. 