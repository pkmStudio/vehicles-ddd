<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

/**
 * Объектное поле, видимое по значению другого поля (зависимости).
 * Ядро — без Filament: в `toArray()` отдаёт структуру ObjectField + `dependency`/`dependency_value`.
 * Условную видимость рендерит адаптер UI (Filament) в сервисе с интерфейсом.
 */
final readonly class ConditionalObjectField extends AbstractField
{
    private ObjectField $field;

    public function __construct(
        string $name,
        ?string $label,
        array $rules = [],
        bool $required = false,
        array $children = [],
        string $containerType = 'fieldset',
        private string $dependency = '',
        private string $dependencyValue = '',
    ) {
        parent::__construct($name, $label, $rules, $required);

        $this->field = new ObjectField(
            name: $name,
            label: $label ?? '',
            rules: $rules,
            required: $required,
            children: $children,
            containerType: $containerType,
        );
    }

    public function getType(): string
    {
        return $this->field->getType();
    }

    public function toArray(): array
    {
        $values = $this->field->toArray();

        if ($this->dependency !== '') {
            $values['dependency'] = $this->dependency;
            $values['dependency_value'] = $this->dependencyValue;
        }

        return $values;
    }
}
