<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Vehicle\Templates;

use Dan\FieldTemplates\AbstractTemplate;
use Dan\FieldTemplates\CommonFields;
use Dan\FieldTemplates\Fields\NumericField;
use Dan\FieldTemplates\Fields\ObjectField;

final readonly class WiperTemplate extends AbstractTemplate
{
    protected function initializeFields(): array
    {
        return [
            $this->createFrontField(),
            $this->createBackField(),
        ];
    }

    private function createFrontField(): ObjectField
    {
        return new ObjectField(
            name: 'front',
            label: 'Параметры передних щеток',
            rules: ['array'],
            required: false,
            children: [
                CommonFields::range(
                    name: 'length_main',
                    label: 'Размеры водительской щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::range(
                    name: 'length_second',
                    label: 'Размеры пассажирской щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::adapterTypeFront(),
                new NumericField(
                    name: 'count_wipers',
                    label: 'Количество передних щеток',
                    rules: ['integer', 'min:0'],
                    required: false,
                    isInteger: true,
                ),
            ],
            containerType: 'section'
        );
    }

    private function createBackField(): ObjectField
    {
        return new ObjectField(
            name: 'back',
            label: 'Параметры задних щеток',
            rules: ['array'],
            required: false,
            children: [
                CommonFields::range(
                    name: 'length_rear',
                    label: 'Размеры задней щетки в мм',
                    required: false,
                    addLabelItem: true,
                ),
                CommonFields::adapterTypeRear(),
                new NumericField(
                    name: 'count_wipers',
                    label: 'Количество задних щеток',
                    rules: ['integer', 'min:0'],
                    required: false,
                    isInteger: true,
                ),
            ],
            containerType: 'section'
        );
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
