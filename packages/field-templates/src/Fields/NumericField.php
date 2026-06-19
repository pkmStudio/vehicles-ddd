<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

final readonly class NumericField extends TextField
{
    public function __construct(
        string $name,
        string $label,
        array $rules = [],
        bool $required = false,
        private bool $isInteger = false,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return $this->isInteger ? 'integer' : 'numeric';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'is_integer' => $this->isInteger,
            'step' => $this->isInteger ? 1 : 0.01,
        ]);
    }
}
