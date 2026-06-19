<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

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

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'dependency' => $this->dependency,
            'options_source' => $this->optionsSource,
            'multiple' => $this->multiple,
        ]);
    }
}
