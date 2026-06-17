<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

final readonly class ObjectField extends AbstractField
{
    public function __construct(
        string $name,
        string $label,
        array $rules = [],
        bool $required = false,
        private array $children = [],
        private string $containerType = 'fieldset',
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'object';
    }

    public function toFilamentForm(string $statePath = ''): Component
    {
        $fields = [];
        foreach ($this->children as $childField) {
            $childStatePath = $statePath ? $statePath.'.'.$this->name : $this->name;
            $fields[] = $childField->toFilamentForm($childStatePath);
        }

        $columns = count($fields) % 2 == 0 ? 2 : min(count($fields), 3);

        return match ($this->containerType) {
            'fieldset' => Fieldset::make($this->label)
                ->schema($fields)
                ->columnSpanFull(),

            'group' => Group::make()
                ->schema($fields),

            'section' => Section::make($this->label)
                ->schema([Grid::make($columns)->schema($fields)])
                ->collapsible()
                ->collapsed()
                ->compact()
                ->columnSpanFull(),

            'tab' => Tabs::make($this->name)
                ->tabs($this->buildTabs())
                ->columnSpanFull(),

            default => Grid::make($columns)->schema($fields),
        };
    }

    private function buildTabs(): array
    {
        $tabs = [];
        foreach ($this->children as $key => $childField) {
            if ($childField instanceof ObjectField) {
                $tabs[] = Tab::make($childField->label)
                    ->schema($childField->toFilamentForm($key)->getChildComponents());
            } else {
                $tabs[] = Tab::make($childField->label)
                    ->schema([$childField->toFilamentForm($key)]);
            }
        }

        return $tabs;
    }

    public function toArray(): array
    {
        $childrenArray = [];
        foreach ($this->children as $child) {
            $childrenArray[$child->getName()] = $child->toArray();
        }

        return array_merge(parent::toArray(), [
            'children' => $childrenArray,
            'container_type' => $this->containerType,
        ]);
    }
}
