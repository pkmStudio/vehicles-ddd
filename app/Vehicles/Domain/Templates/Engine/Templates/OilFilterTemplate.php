<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Engine\Templates;

use Dan\FieldTemplates\AbstractTemplate;
use Dan\FieldTemplates\Attributes\Filter\OilFilterFatherEnum;
use Dan\FieldTemplates\Attributes\Filter\OilFilterThreadEnum;
use Dan\FieldTemplates\Attributes\Filter\PerformanceEnum;
use Dan\FieldTemplates\CommonFields;
use Dan\FieldTemplates\Fields\ConditionalSelectField;
use Dan\FieldTemplates\Fields\NumericField;

final readonly class OilFilterTemplate extends AbstractTemplate
{
    protected function initializeFields(): array
    {
        return [
            CommonFields::performance(live: true),
            CommonFields::form(),
            new ConditionalSelectField(
                name: 'father',
                label: 'Резьба или Папа',
                rules: ['string', 'max:30'],
                required: false,
                dependency: 'performance',
                optionsSource: [
                    PerformanceEnum::WIND_UP->name => OilFilterThreadEnum::toArray(),
                    PerformanceEnum::DIRECT_FLOW->name => OilFilterFatherEnum::toArray(),
                ],
            ),
            new NumericField(
                name: 'diameter',
                label: 'Диаметр (мм)',
                rules: ['integer', 'min:0'],
                isInteger: true,
            ),
            new NumericField(
                name: 'mother',
                label: 'Диаметр внешний уплотнителя (мм) или мама',
                rules: ['integer', 'min:0'],
                isInteger: true,
            ),
            CommonFields::metrics(),
        ];
    }

    public function getName(): string
    {
        return 'Масляный фильтр';
    }

    public function getTemplateKey(): string
    {
        return 'oil_filter';
    }
}
