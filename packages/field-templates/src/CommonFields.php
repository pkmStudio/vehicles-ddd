<?php

declare(strict_types=1);

namespace Dan\FieldTemplates;

use Dan\FieldTemplates\Attributes\BooleanOptionEnum;
use Dan\FieldTemplates\Attributes\Filter\FormEnum;
use Dan\FieldTemplates\Attributes\Filter\PerformanceEnum;
use Dan\FieldTemplates\Attributes\Filter\TypeEnum;
use Dan\FieldTemplates\Attributes\PositionEnum;
use Dan\FieldTemplates\Attributes\Wiper\FrontAdapterTypeEnum;
use Dan\FieldTemplates\Attributes\Wiper\RearAdapterTypeEnum;
use Dan\FieldTemplates\Fields\ArrayField;
use Dan\FieldTemplates\Fields\NumericField;
use Dan\FieldTemplates\Fields\ObjectField;
use Dan\FieldTemplates\Fields\SelectField;

final readonly class CommonFields
{
    /**
     * Базовые метрики (длина, ширина, высота)
     */
    public static function metrics(
        bool $required = true,
        string $containerType = 'default'
    ): ObjectField {
        return new ObjectField(
            name: 'metrics',
            label: 'Размеры',
            rules: ['array'],
            children: [
                self::length($required),
                self::width($required),
                self::height($required),
            ],
            containerType: $containerType,
        );
    }

    public static function length(bool $required = true): ArrayField
    {
        return new ArrayField(
            name: 'length',
            label: 'Длина (мм)',
            required: $required,
            itemType: 'integer',
            itemRules: ['integer', 'min:0'],
        );
    }

    public static function width(bool $required = true): ArrayField
    {
        return new ArrayField(
            name: 'width',
            label: 'Ширина (мм)',
            required: $required,
            itemType: 'integer',
            itemRules: ['integer', 'min:0'],
        );
    }

    public static function height(bool $required = true): ArrayField
    {
        return new ArrayField(
            name: 'height',
            label: 'Высота (мм)',
            required: $required,
            itemType: 'integer',
            itemRules: ['integer', 'min:0'],
        );
    }

    /**
     * Позиция/расположение
     */
    public static function position(bool $required = true): SelectField
    {
        return new SelectField(
            name: 'position',
            label: 'Расположение',
            rules: ['string', 'max:30'],
            required: $required,
            options: PositionEnum::toArray(),
        );
    }

    /**
     * Булево поле (Да/Нет)
     */
    public static function booleanField(
        string $name,
        string $label,
        bool $required = true
    ): SelectField {
        return new SelectField(
            name: $name,
            label: $label,
            rules: ['string'],
            required: $required,
            options: BooleanOptionEnum::toArray(),
        );
    }

    /**
     * Исполнение фильтра
     */
    public static function performance(bool $required = true, bool $live = false): SelectField
    {
        return new SelectField(
            name: 'performance',
            label: 'Исполнение фильтра',
            rules: ['string', 'max:30'],
            required: $required,
            options: PerformanceEnum::toArray(),
            live: $live,
        );
    }

    /**
     * Форма фильтра
     */
    public static function form(bool $required = true): SelectField
    {
        return new SelectField(
            name: 'form',
            label: 'Форма фильтра',
            rules: ['string', 'max:30'],
            required: $required,
            options: FormEnum::toArray(),
        );
    }

    /**
     * Вид фильтра
     */
    public static function filterType(bool $required = true): SelectField
    {
        return new SelectField(
            name: 'filter_type',
            label: 'Вид фильтра',
            rules: ['string', 'max:30'],
            required: $required,
            options: TypeEnum::toArray(),
        );
    }

    /**
     * Диапазон (min/max)
     */
    public static function range(
        string $name,
        string $label,
        bool $required = true,
        string $containerType = 'fieldset',
        bool $addLabelItem = false,
    ): ObjectField {
        return new ObjectField(
            name: $name,
            label: $label,
            rules: ['array'],
            children: [
                new NumericField(
                    name: 'min',
                    label: $addLabelItem ? $label.' От' : 'От',
                    rules: ['numeric', 'min:0'],
                    required: $required,
                    isInteger: false,
                ),
                new NumericField(
                    name: 'max',
                    label: $addLabelItem ? $label.' До' : 'До',
                    rules: ['numeric', 'min:0'],
                    required: $required,
                    isInteger: false,
                ),
            ],
            containerType: $containerType,
        );
    }

    /**
     * Диапазон целых чисел (мин/макс).
     */
    public static function rangeInteger(
        string $name,
        string $label,
        bool $required = true,
        string $containerType = 'fieldset',
        bool $addLabelItem = false,
    ): ObjectField {
        return new ObjectField(
            name: $name,
            label: $label,
            rules: ['array'],
            children: [
                new NumericField(
                    name: 'min',
                    label: $addLabelItem ? $label.' От' : 'От',
                    rules: ['integer', 'min:0'],
                    required: $required,
                    isInteger: true,
                ),
                new NumericField(
                    name: 'max',
                    label: $addLabelItem ? $label.' До' : 'До',
                    rules: ['integer', 'min:0'],
                    required: $required,
                    isInteger: true,
                ),
            ],
            containerType: $containerType,
        );
    }

    public static function adapterTypeFront(): SelectField
    {
        return new SelectField(
            name: 'adapter_type_front',
            label: 'Тип крепления передних',
            rules: ['array'],
            options: FrontAdapterTypeEnum::toArray(),
            multiple: true,
        );
    }

    /**
     * Адаптер передних дворников для шаблона ТС: один код на сторону (`max:1`),
     * структура данных остаётся массивом (UI-ограничение, не смена модели).
     */
    public static function adapterTypeFrontForVehicle(): SelectField
    {
        return new SelectField(
            name: 'adapter_type_front',
            label: 'Тип крепления передних',
            rules: ['array', 'max:1'],
            options: FrontAdapterTypeEnum::toArray(),
            multiple: true,
            maxItems: 1,
        );
    }

    public static function adapterTypeRear(): SelectField
    {
        return new SelectField(
            name: 'adapter_type_rear',
            label: 'Тип крепления задней',
            rules: ['array'],
            options: RearAdapterTypeEnum::toArray(),
            multiple: true,
        );
    }

    /**
     * Адаптер задних дворников для шаблона ТС: один код на сторону (`max:1`),
     * структура данных остаётся массивом (UI-ограничение, не смена модели).
     */
    public static function adapterTypeRearForVehicle(): SelectField
    {
        return new SelectField(
            name: 'adapter_type_rear',
            label: 'Тип крепления задней',
            rules: ['array', 'max:1'],
            options: RearAdapterTypeEnum::toArray(),
            multiple: true,
            maxItems: 1,
        );
    }
}
