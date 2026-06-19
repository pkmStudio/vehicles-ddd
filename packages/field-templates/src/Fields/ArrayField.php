<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

final readonly class ArrayField extends AbstractField
{
    public function __construct(
        string $name,
        string $label,
        array $rules = [],
        bool $required = false,
        private string $itemType = 'string',
        private array $itemRules = [],
        private ?string $addAdditionalLabel = null,
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private bool $reorderable = true,
    ) {
        parent::__construct($name, $label, $rules, $required);
    }

    public function getType(): string
    {
        return 'array';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'item_type' => $this->itemType,
            'item_rules' => implode('|', $this->itemRules),
            'add_action_label' => $this->addAdditionalLabel,
            'reorderable' => $this->reorderable,
            'min_items' => $this->minItems,
            'max_items' => $this->maxItems,
        ]);
    }
}
