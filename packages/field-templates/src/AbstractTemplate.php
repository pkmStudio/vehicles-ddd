<?php

declare(strict_types=1);

namespace Dan\FieldTemplates;

use Dan\FieldTemplates\Fields\AbstractField;

abstract readonly class AbstractTemplate
{
    /** @var AbstractField[] */
    protected array $fields;

    public function __construct()
    {
        $this->fields = $this->initializeFields();
    }

    /**
     * Инициализация полей шаблона
     *
     * @return AbstractField[]
     */
    abstract protected function initializeFields(): array;

    /**
     * Имя шаблона для отображения
     */
    abstract public function getName(): string;

    /**
     * Ключ шаблона для идентификации в системе
     */
    abstract public function getTemplateKey(): string;

    /**
     * Получить массив-структуру (контракт для рендера в UI-сервисе и для headless-импорта).
     */
    public function getArrayTemplate(): array
    {
        $structure = [];
        foreach ($this->fields as $field) {
            $structure[$field->getName()] = $field->toArray();
        }

        return $structure;
    }

    /**
     * Получить все поля
     *
     * @return AbstractField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Получить поле по имени
     */
    public function getField(string $name): ?AbstractField
    {
        return array_find($this->fields, fn ($field) => $field->getName() === $name);
    }
}
