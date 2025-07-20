<?php

namespace JohnCarter\FilamentNestedSortable\Pages;

use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Illuminate\Contracts\View\View;

abstract class NestedSortablePage extends Page
{
    protected static string $view = 'filament-nested-sortable::pages.nested-sortable-page';

    protected ?string $modelClass = null;
    protected string $parentColumn = 'parent_id';
    protected string $orderColumn = 'order';
    protected string $displayColumn = 'title';
    protected string $childrenRelationship = 'children';
    protected string $parentRelationship = 'parent';
    protected int $maxDepth = 10;

    public bool $hasChanges = false;

    public function mount(): void
    {
        if (method_exists(parent::class, 'mount')) {
            parent::mount();
        }

        $this->configureTree();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return parent::render();
    }

    protected function configureTree(): void
    {
        // Override this method in child classes to configure the tree
    }

    public function getTitle(): string
    {
        return 'Tree View';
    }

    #[On('getTreeItems')]
    public function getTreeItems(): array
    {
        // Ensure tree is configured
        if ($this->modelClass === null) {
            $this->configureTree();
        }

        $modelClass = $this->getModelClass();

        // Get all items and convert to array
        $allItems = $modelClass::query()
            ->orderBy($this->getParentColumn())
            ->orderBy($this->getOrderColumn())
            ->get()
            ->map(function ($item) {
                $parentId = $item->{$this->getParentColumn()};

                return [
                    'id' => $item->id,
                    'parent_id' => $parentId == -1 ? null : $parentId, // Convert -1 back to null for frontend
                    'order' => $item->{$this->getOrderColumn()} ?? 0,
                    'display' => $item->{$this->getDisplayColumn()},
                    'children' => [], // Initialize empty children array
                ];
            })
            ->toArray();

        // Build hierarchical tree structure
        return $this->buildTree($allItems);
    }

    protected function buildTree(array $items, $parentId = null): array
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                // Find children for this item
                $item['children'] = $this->buildTree($items, $item['id']);
                $tree[] = $item;
            }
        }

        return $tree;
    }

    protected function calculateDepth(Model $item): int
    {
        $depth = 0;
        $current = $item;

        while ($current->{$this->getParentRelationship()}) {
            $depth++;
            $current = $current->{$this->getParentRelationship()};

            if ($depth > $this->getMaxDepth()) {
                break;
            }
        }

        return $depth;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->discardTreeAction(),
            $this->saveTreeAction(),
        ];
    }

    protected function saveTreeAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-nested-sortable::actions.save_changes'))
            ->color('primary')
            ->action('saveTreeChanges')
            ->disabled(fn() => !$this->hasChanges());
    }

    protected function discardTreeAction(): Action
    {
        return Action::make('discard')
            ->label(__('filament-nested-sortable::actions.discard_changes'))
            ->color('gray')
            ->action('discardTreeChanges')
            ->disabled(fn() => !$this->hasChanges());
    }

    public function saveTreeChanges(): void
    {
        $this->dispatch('save-tree-changes');
    }

    public function persistTreeChanges(array $updatedItems): void
    {
        $modelClass = $this->getModelClass();
        $parentColumn = $this->getParentColumn();
        $orderColumn = $this->getOrderColumn();

        // Start a database transaction
        \DB::transaction(function () use ($modelClass, $parentColumn, $orderColumn, $updatedItems) {
            foreach ($updatedItems as $item) {
                $model = $modelClass::find($item['id']);

                if ($model) {
                    // Update the model with new order and parent_id
                    $model->update([
                        $orderColumn => $item['order'],
                        $parentColumn => $item['parent_id'] ?? -1,
                    ]);
                }
            }
        });

        // Show success notification
        Notification::make()
            ->title(__('filament-nested-sortable::notifications.tree_updated'))
            ->success()
            ->send();

        // Refresh the tree items after saving
        $this->dispatch('tree-changes-saved');
    }

    public function discardTreeChanges(): void
    {
        $this->dispatch('discard-tree-changes');
    }

    protected function hasChanges(): bool
    {
        return $this->hasChanges;
    }

    public function setHasChanges(bool $value): void
    {
        $this->hasChanges = $value;
    }

    // Configuration getters
    protected function getModelClass(): string
    {
        // Try to get model class from the resource first
        if ($this->modelClass === null && method_exists($this, 'getResource')) {
            $resource = $this->getResource();
            if ($resource && method_exists($resource, 'getModel')) {
                return $resource::getModel();
            }
        }

        // Fallback to configured model class
        if ($this->modelClass === null) {
            throw new \RuntimeException('Model class not configured. Please call configureTree() or use modelClass() setter.');
        }
        return $this->modelClass;
    }

    protected function getParentColumn(): string
    {
        return $this->parentColumn;
    }

    protected function getOrderColumn(): string
    {
        return $this->orderColumn;
    }

    protected function getDisplayColumn(): string
    {
        return $this->displayColumn;
    }

    protected function getChildrenRelationship(): string
    {
        return $this->childrenRelationship;
    }

    protected function getParentRelationship(): string
    {
        return $this->parentRelationship;
    }

    protected function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    // Configuration setters
    public function modelClass(string $modelClass): static
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function parentColumn(string $column): static
    {
        $this->parentColumn = $column;
        return $this;
    }

    public function orderColumn(string $column): static
    {
        $this->orderColumn = $column;
        return $this;
    }

    public function displayColumn(string $column): static
    {
        $this->displayColumn = $column;
        return $this;
    }

    public function childrenRelationship(string $relationship): static
    {
        $this->childrenRelationship = $relationship;
        return $this;
    }

    public function parentRelationship(string $relationship): static
    {
        $this->parentRelationship = $relationship;
        return $this;
    }

    public function maxDepth(int $depth): static
    {
        $this->maxDepth = $depth;
        return $this;
    }
}
