<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Templates\Vehicle\Templates;

use App\Vehicles\Shared\Domain\Enums\Vehicle\WiperSideEnum;
use Dan\FieldTemplates\AbstractTemplate;
use Dan\FieldTemplates\CommonFields;
use Dan\FieldTemplates\Fields\ConditionalObjectField;
use Dan\FieldTemplates\Fields\NumericField;
use Dan\FieldTemplates\Fields\SelectField;

/**
 * Дворники хранятся по одной спецификации на сторону (front/back).
 * `position` — переключатель стороны: чисто UI-конструкция формы, в данные не попадает
 * (вырезается в getArrayTemplate). front/back — условные блоки, видимые по выбранной стороне.
 */
final readonly class WiperTemplate extends AbstractTemplate
{
    protected function initializeFields(): array
    {
        return [
            $this->createPositionField(),
            $this->createFrontField(),
            $this->createBackField(),
        ];
    }

    private function createPositionField(): SelectField
    {
        return new SelectField(
            name: 'position',
            label: 'Положение щеток',
            rules: ['string', 'max:30'],
            required: true,
            options: [
                WiperSideEnum::FRONT->value => 'Передняя',
                WiperSideEnum::BACK->value => 'Задняя',
            ],
            live: true,
        );
    }

    private function createFrontField(): ConditionalObjectField
    {
        return new ConditionalObjectField(
            name: 'front',
            label: 'Параметры передних щеток',
            rules: ['array'],
            required: false,
            children: [
                CommonFields::rangeInteger(
                    name: 'length_main',
                    label: 'Размеры водительской щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::rangeInteger(
                    name: 'length_second',
                    label: 'Размеры пассажирской щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::adapterTypeFrontForVehicle(),
                new NumericField(
                    name: 'count_wipers',
                    label: 'Количество передних щеток',
                    rules: ['integer', 'min:0'],
                    required: false,
                    isInteger: true,
                ),
            ],
            containerType: 'section',
            dependency: 'position',
            dependencyValue: WiperSideEnum::FRONT->value,
        );
    }

    private function createBackField(): ConditionalObjectField
    {
        return new ConditionalObjectField(
            name: 'back',
            label: 'Параметры задних щеток',
            rules: ['array'],
            required: false,
            children: [
                CommonFields::rangeInteger(
                    name: 'length_rear',
                    label: 'Размеры задней щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::adapterTypeRearForVehicle(),
                new NumericField(
                    name: 'count_wipers',
                    label: 'Количество задних щеток',
                    rules: ['integer', 'min:0'],
                    required: false,
                    isInteger: true,
                ),
            ],
            containerType: 'section',
            dependency: 'position',
            dependencyValue: WiperSideEnum::BACK->value,
        );
    }

    public function getArrayTemplate(): array
    {
        $fields = parent::getArrayTemplate();

        unset($fields['position']);

        return $fields;
    }

    public function getName(): string
    {
        return 'Щетки стеклоочистителя';
    }

    public function getTemplateKey(): string
    {
        return 'wiper';
    }
}
