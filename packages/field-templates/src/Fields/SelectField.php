<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;

final readonly class SelectField extends AbstractField
{
    public function __construct(
        string $name,
        ?string $label,
        array $rules = [],
        bool $required = false,
        private array $options = [],
        private bool $multiple = false,
        private bool $live = false,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'select';
    }

    public function toFilamentForm(string $statePath = ''): Field
    {
        $name = $statePath ? $statePath.'.'.$this->name : $this->name;
        $field = Select::make($name)
            ->label($this->label)
            ->options($this->options)
            ->rules($this->rules)
            ->required($this->required);

        if ($this->live) {
            $field->live();
        }

        if ($this->multiple) {
            $field->multiple()->searchable(false);
        }

        return $field;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'variables' => $this->options,
            'multiple' => $this->multiple,
            'is_live' => $this->live,
        ]);
    }
}
