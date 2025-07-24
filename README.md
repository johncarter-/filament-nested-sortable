# Filament Nested Sortable Plugin

A Filament Panels plugin for ordering and nesting model records.

## Usage

## Tailwind CSS Configuration

Since this is a local package, Tailwind classes used in the plugin views need to be included in your Filament CSS build. Add the plugin's view paths to your `resources/css/filament/cp/theme.css` file:

```css
@import '../../../../local_packages/filament-nested-sortable/resources/views/**/*.blade.php';
```