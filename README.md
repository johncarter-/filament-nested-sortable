# Filament Nested Sortable Plugin

A Filament Panels plugin for ordering and nesting model records.

![Screenshot](images/screenshot.png)

## Installation

You can install the package via composer:

```bash
composer require johncarter/filament-nested-sortable
```

## Usage

Ensure your model has an `order` integer column and an integer `parent_id` with a **default to `-1`**.

### 1. Create a new page in your resource
e.g. `app/Filament/Resources/PageResource/Pages/TreeListPages.php`

Make sure the page extends `JohnCarter\FilamentNestedSortable\Pages\NestedSortablePage`.

```php
use JohnCarter\FilamentNestedSortable\Pages\NestedSortablePage;

class TreeListPages extends NestedSortablePage
{
    // ...
}
```

### 2. Add the page to your main PageResource Page
```php
public static function getPages(): array
{
    return [
        'index' => Pages\TreeListPages::route('/'),
    ];
}
```

### 3. Add plugins Tailwind CSS class content
Add the plugin's view paths to your `resources/css/filament/cp/theme.css` file:

```css
@import '/vendor/johncarter/filament-nested-sortable/resources/views/**/*.blade.php';
```

### 4. Modify the create record form:

```php
public function getCreateRecordFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->reactive()
                ->afterStateUpdated(
                    fn($state, callable $set) =>
                    $set('slug', Str::slug($state))
                ),

            TextInput::make('slug')
                ->extraAttributes(['class' => 'font-mono text-gray-500']),
        ];
    }
```

### 5. Add actions to the record action group
```php

    public function getRecordActions(): array
    {
        $actions = parent::getRecordActions();

        array_unshift($actions, $this->viewAction());

        return $actions;
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->icon('heroicon-o-eye')
            ->openUrlInNewTab()
            ->url(function (array $arguments) {
                return $arguments['record']['url'];
            });
    }
```

## Customization

The package provides several methods and properties that you can override in your page class to customize the default behavior:

### Overridable Properties

```php
class TreeListPages extends NestedSortablePage
{
    public string $recordKeyName = 'uuid';           // Default: 'id'
    public string $parentColumn = 'category_id';     // Default: 'parent_id'
    public string $orderColumn = 'sort_order';       // Default: 'order'
    public string $childrenRelationName = 'subcategories'; // Default: 'children'
    protected string $view = 'custom::nested-sortable-page'; // Default: 'filament-nested-sortable::pages.nested-sortable-page'
}
```

### Overridable Methods

#### Data Retrieval & Display

```php
// Customize record fetching
public function getRecords(): EloquentCollection
{
    return $this->getResource()::getEloquentQuery()
        ->with([$this->childrenRelationName, 'author'])
        ->where('is_published', true)
        ->orderBy($this->orderColumn)
        ->get();
}

// Change label column (default: 'title')
public function getRecordLabelColumn(): string
{
    return 'name';
}

// Enable/disable clickable records (default: true)
public function hasRecordUrl(): bool
{
    return false;
}

// Customize record URLs
public function getRecordUrl($record): string
{
    return route('pages.show', $record['slug']);
}

// Customize label rendering
public function getRecordLabel($record): Htmlable | string
{
    $label = $record->{$this->getRecordLabelColumn()};
    return $this->hasRecordUrl() 
        ? new HtmlString('<a href="' . $this->getRecordUrl($record) . '">' . $label . '</a>')
        : $label;
}
```

#### Page Configuration

```php
// Customize page title
public function getTitle(): string
{
    return 'Category Management';
}
```

#### Record Actions

```php
// Add/remove actions
public function getRecordActions(): array
{
    $actions = parent::getRecordActions();
    array_unshift($actions, $this->viewAction());
    return $actions;
}

// Override edit action
public function editAction(): Action
{
    return Action::make('edit')
        ->icon('heroicon-o-pencil')
        ->color('warning')
        ->url(fn($arguments) => $this->getResource()::getUrl('edit', ['record' => $arguments['record'][$this->recordKeyName]]));
}

// Override delete action
public function deleteAction(): Action
{
    return Action::make('delete')
        ->label('Remove')
        ->color('danger')
        ->requiresConfirmation()
        ->action(fn($arguments) => $this->deleteRecord($arguments['record'][$this->recordKeyName]));
}
```

#### Header Actions

```php
// Customize header actions
public function getHeaderActions(): array
{
    return [
        Action::make('create')
            ->label('Add New Category')
            ->schema($this->getCreateRecordFormSchema())
            ->action(fn($data) => $this->createRecord($data)),
        Action::make('import')
            ->url(route('categories.import')),
    ];
}
```

#### Form & Creation

```php
// Customize create form
public function getCreateRecordFormSchema(): array
{
    return [
        TextInput::make('name')->required(),
        TextInput::make('slug')->unique('categories'),
        Select::make('parent_id')->options($this->getParentOptions()),
    ];
}

// Customize creation logic
public function createRecord(array $data): void
{
    $data['order'] = $this->getNextOrder();
    $this->getResource()::getModel()::create($data);
    $this->records = $this->getRecords();
}
```

#### Record Updates

```php
// Customize update logic
public function persistRecordUpdates($pendingRecordUpdates)
{
    foreach ($pendingRecordUpdates as $update) {
        $record = $this->getResource()::getModel()::find($update[$this->recordKeyName]);
        $record->update($update);
    }
    $this->records = $this->getRecords();
    $this->dispatch('reset-pending-record-updates');
}
```

These customization options allow you to tailor the nested sortable functionality to match your specific application requirements while maintaining the core drag-and-drop ordering and nesting capabilities.
