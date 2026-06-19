<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Fields;

/**
 * Базовое поле шаблона. Чистое описание + сериализация в массив (`toArray()`).
 * Рендер в UI (например, Filament-форму) — отдельный адаптер в сервисе с UI,
 * он потребляет результат `AbstractTemplate::getArrayTemplate()`. Пакет — без Filament.
 */
abstract readonly class AbstractField
{
    public function __construct(
        protected string $name,
        protected ?string $label,
        protected array $rules = [],
        protected bool $required = false,
    ) {}

    abstract public function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'name' => $this->label ?? '',
            'rules' => $this->buildRulesString(),
        ];
    }

    protected function buildRulesString(): string
    {
        $rules = $this->rules;
        if ($this->required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return implode('|', $rules);
    }
}
