<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Engine\Templates;

use Dan\FieldTemplates\AbstractTemplate;
use Dan\FieldTemplates\CommonFields;
use Dan\FieldTemplates\Fields\NumericField;

final readonly class AirFilterTemplate extends AbstractTemplate
{
    protected function initializeFields(): array
    {
        return [
            CommonFields::form(),
            CommonFields::length(),
            CommonFields::width(),
            CommonFields::height(),
            new NumericField(
                name: 'diameter',
                label: 'Диаметр (мм)',
                rules: ['numeric', 'min:0'],
                required: false,
                isInteger: false,
            ),
        ];
    }

    public function getName(): string
    {
        return 'Воздушный фильтр';
    }

    public function getTemplateKey(): string
    {
        return 'air_filter';
    }
}
