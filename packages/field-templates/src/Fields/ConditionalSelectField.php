<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

final readonly class ConditionalSelectField extends AbstractField
{
    public function __construct(
        string $name,
        ?string $label,
        array $rules,
        bool $required,
        private string $dependency,
        private array $optionsSource = [],
        private bool $multiple = false,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'conditional_select';
    }

    public function toFilamentForm(string $statePath = ''): Field
    {
        $name = $statePath ? $statePath.'.'.$this->name : $this->name;
        $dependency = $statePath ? $statePath.'.'.$this->dependency : $this->dependency;

        return Select::make($name)
            ->label($this->label)
            ->options(function (Get $get) use ($dependency) {
                $dependencyValue = $get($dependency);

                return $this->optionsSource[$dependencyValue] ?? $this->optionsSource['default'] ?? [];
            })
            ->hidden(function (Get $get) use ($dependency) {
                $dependencyValue = $get($dependency);

                return ! isset($this->optionsSource[$dependencyValue]);
            })
            ->multiple($this->multiple)
            ->rules($this->rules)
            ->required($this->required);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'dependency' => $this->dependency,
            'options_source' => $this->optionsSource,
            'multiple' => $this->multiple,
        ]);
    }
}
