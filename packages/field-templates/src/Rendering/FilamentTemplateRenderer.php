<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Rendering;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;

/**
 * ОПЦИОНАЛЬНЫЙ адаптер: собирает Filament-форму из AbstractTemplate::getArrayTemplate().
 *
 * Ядро пакета (Fields/*, AbstractTemplate) — чистый PHP без Filament. Этот класс — единственное
 * место с зависимостью от Filament, и она «спящая»: класс ОПРЕДЕЛЯЕТСЯ без установленного Filament
 * (use — это алиасы, типы в сигнатурах резолвятся лениво), Filament подгружается только при вызове
 * render(). Headless-сервисы (без UI) пакет используют, этот класс не трогают — Filament им не нужен.
 *
 * Контракт со ядром — структура массива getArrayTemplate(), не объекты полей.
 * @deprecated
 */
final class FilamentTemplateRenderer
{
    /**
     * @param  array<string, array>  $template  результат AbstractTemplate::getArrayTemplate()
     * @return array<int, Field|Component>
     */
    public function render(array $template, string $statePath = ''): array
    {
        $components = [];
        foreach ($template as $machineName => $field) {
            $components[] = $this->renderField($machineName, $field, $statePath);
        }

        return $components;
    }

    private function renderField(string $name, array $field, string $statePath): Field|Component
    {
        return match ($field['type']) {
            'object' => $this->renderObject($name, $field, $statePath),
            'array' => $this->renderArray($name, $field, $statePath),
            'select' => $this->renderSelect($name, $field, $statePath),
            'conditional_select' => $this->renderConditionalSelect($name, $field, $statePath),
            'numeric', 'integer' => $this->renderNumeric($name, $field, $statePath),
            default => $this->renderText($name, $field, $statePath),
        };
    }

    private function path(string $statePath, string $name): string
    {
        return $statePath ? "{$statePath}.{$name}" : $name;
    }

    /**
     * Разбирает pipe-строку правил обратно на [правила, required?].
     *
     * @return array{0: array<int, string>, 1: bool}
     */
    private function rules(string $rulesString): array
    {
        $rules = array_filter(explode('|', $rulesString));
        $required = in_array('required', $rules, true);
        $rules = array_values(array_diff($rules, ['required', 'nullable']));

        return [$rules, $required];
    }

    private function renderText(string $name, array $field, string $statePath): Field
    {
        [$rules, $required] = $this->rules($field['rules']);

        return TextInput::make($this->path($statePath, $name))
            ->label($field['name'])
            ->rules($rules)
            ->required($required);
    }

    private function renderNumeric(string $name, array $field, string $statePath): Field
    {
        $input = $this->renderText($name, $field, $statePath)
            ->numeric()
            ->step($field['step'] ?? (! empty($field['is_integer']) ? 1 : 0.01));

        if (! empty($field['is_integer'])) {
            $input->mutateDehydratedStateUsing(fn ($state) => $state !== null ? (int) $state : null);
        }

        return $input;
    }

    private function renderSelect(string $name, array $field, string $statePath): Field
    {
        [$rules, $required] = $this->rules($field['rules']);
        $select = Select::make($this->path($statePath, $name))
            ->label($field['name'])
            ->options($field['variables'] ?? [])
            ->rules($rules)
            ->required($required);

        if (! empty($field['is_live'])) {
            $select->live();
        }
        if (! empty($field['multiple'])) {
            $select->multiple()->searchable(false);
        }

        return $select;
    }

    private function renderConditionalSelect(string $name, array $field, string $statePath): Field
    {
        [$rules, $required] = $this->rules($field['rules']);
        $dependency = $this->path($statePath, $field['dependency']);
        $source = $field['options_source'] ?? [];

        return Select::make($this->path($statePath, $name))
            ->label($field['name'])
            ->options(fn (Get $get) => $source[$get($dependency)] ?? $source['default'] ?? [])
            ->hidden(fn (Get $get) => ! isset($source[$get($dependency)]))
            ->multiple($field['multiple'] ?? false)
            ->rules($rules)
            ->required($required);
    }

    private function renderArray(string $name, array $field, string $statePath): Field
    {
        [$itemRules] = $this->rules($field['item_rules'] ?? '');
        $simple = match ($field['item_type'] ?? 'string') {
            'numeric' => TextInput::make('value')->label('Значение')->numeric()->step(0.01)->required()->rules($itemRules),
            'integer' => TextInput::make('value')->label('Значение')->numeric()->step(1)->required()->rules($itemRules),
            default => TextInput::make('value')->label('Значение')->required()->rules($itemRules),
        };

        [$rules] = $this->rules($field['rules']);
        $repeater = Repeater::make($this->path($statePath, $name))
            ->label($field['name'])
            ->simple($simple)
            ->addActionAlignment(Alignment::Start)
            ->rules($rules);

        if (! empty($field['min_items'])) {
            $repeater->minItems($field['min_items']);
        }
        if (! empty($field['max_items'])) {
            $repeater->maxItems($field['max_items']);
        }
        if (! empty($field['add_action_label'])) {
            $repeater->addActionLabel($field['add_action_label']);
        }
        if (! empty($field['reorderable'])) {
            $repeater->reorderable();
        }

        return $repeater;
    }

    private function renderObject(string $name, array $field, string $statePath): Component
    {
        $childPath = $this->path($statePath, $name);
        $children = [];
        foreach ($field['children'] ?? [] as $childName => $child) {
            $children[] = $this->renderField($childName, $child, $childPath);
        }
        $columns = count($children) % 2 === 0 ? 2 : min(count($children), 3);

        return match ($field['container_type'] ?? 'fieldset') {
            'group' => Group::make()->schema($children),
            'section' => Section::make($field['name'])
                ->schema([Grid::make($columns)->schema($children)])
                ->collapsible()->collapsed()->compact()->columnSpanFull(),
            'tab' => Tabs::make($name)->tabs($this->renderTabs($field, $childPath))->columnSpanFull(),
            'grid' => Grid::make($columns)->schema($children),
            default => Fieldset::make($field['name'])->schema($children)->columnSpanFull(),
        };
    }

    /**
     * @return array<int, Tab>
     */
    private function renderTabs(array $field, string $statePath): array
    {
        $tabs = [];
        foreach ($field['children'] ?? [] as $childName => $child) {
            $rendered = $this->renderField($childName, $child, $statePath);
            $schema = $rendered instanceof Component ? $rendered->getChildComponents() : [$rendered];
            $tabs[] = Tab::make($child['name'])->schema($schema);
        }

        return $tabs;
    }
}
