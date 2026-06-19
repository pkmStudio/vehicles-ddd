<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

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
