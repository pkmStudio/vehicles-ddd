<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

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
        private ?int $maxItems = null,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'select';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'variables' => $this->options,
            'multiple' => $this->multiple,
            'is_live' => $this->live,
            'max_items' => $this->maxItems,
        ]);
    }
}
