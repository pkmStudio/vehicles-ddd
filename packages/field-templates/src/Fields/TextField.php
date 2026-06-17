<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;

readonly class TextField extends AbstractField
{
    public function __construct(
        string $name,
        string $label,
        array $rules = [],
        bool $required = false,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'string';
    }

    public function toFilamentForm(string $statePath = ''): Field
    {
        return TextInput::make($statePath ? $statePath.'.'.$this->name : $this->name)
            ->label($this->label)
            ->rules($this->rules)
            ->required($this->required);
    }
}
