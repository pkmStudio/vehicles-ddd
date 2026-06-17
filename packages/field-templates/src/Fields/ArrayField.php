<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Support\Enums\Alignment;

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

    public function toFilamentForm(string $statePath = ''): Field
    {
        $simpleField = $this->createSimpleField();
        $field = Repeater::make($statePath ? $statePath.'.'.$this->name : $this->name)
            ->label($this->label)
            ->simple($simpleField)
            ->addActionAlignment(Alignment::Start)
            ->rules($this->rules);

        if ($this->minItems) {
            $field->minItems($this->minItems);
        }

        if ($this->maxItems) {
            $field->maxItems($this->maxItems);
        }

        if ($this->addAdditionalLabel) {
            $field->addActionLabel($this->addAdditionalLabel);
        }

        if ($this->reorderable) {
            $field->reorderable();
        }

        return $field;
    }

    private function createSimpleField(): Field
    {
        $field = match ($this->itemType) {
            'numeric' => new NumericField(
                name: 'value',
                label: 'Значение',
                rules: $this->itemRules,
                required: true,
                isInteger: false,
            ),

            'integer' => new NumericField(
                name: 'value',
                label: 'Значение',
                rules: $this->itemRules,
                required: true,
                isInteger: true,
            ),

            default => new TextField(
                name: 'value',
                label: 'Значение',
                rules: $this->itemRules,
                required: true,
            )
        };

        return $field->toFilamentForm();
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'item_type' => $this->itemType,
            'item_rules' => implode('|', $this->itemRules),
            'reorderable' => $this->reorderable,
            'min_items' => $this->minItems,
            'max_items' => $this->maxItems,
        ]);
    }
}
