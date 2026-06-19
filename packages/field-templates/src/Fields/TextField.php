<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

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
}
