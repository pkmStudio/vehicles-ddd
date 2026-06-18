<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Engine\Templates;

use Dan\FieldTemplates\Attributes\SparkPlug\ElectrodeGapEnum;
use Dan\FieldTemplates\Attributes\SparkPlug\ThreadLengthEnum;
use Dan\FieldTemplates\Attributes\SparkPlug\ThreadPitchEnum;
use Dan\FieldTemplates\Attributes\SparkPlug\ThreadSizeEnum;
use Dan\FieldTemplates\Attributes\SparkPlug\WrenchJawWidthEnum;
use Dan\FieldTemplates\AbstractTemplate;
use Dan\FieldTemplates\Fields\ObjectField;
use Dan\FieldTemplates\Fields\SelectField;

final readonly class SparkPlugTemplate extends AbstractTemplate
{
    protected function initializeFields(): array
    {
        return [
            $this->createThreadField(),
            $this->createElectrodeField(),
            new SelectField(
                name: 'wrench_jaw_width',
                label: 'Ширина зева гаечного ключа (мм)',
                rules: ['string', 'max:30'],
                required: true,
                options: WrenchJawWidthEnum::toArray(),
            ),
        ];
    }

    private function createThreadField(): ObjectField
    {
        return new ObjectField(
            name: 'thread',
            label: 'Резьба',
            rules: ['array'],
            children: [
                new SelectField(
                    name: 'size',
                    label: 'Размер резьбы',
                    rules: ['string', 'max:30'],
                    required: true,
                    options: ThreadSizeEnum::toArray(),
                ),
                new SelectField(
                    name: 'pitch',
                    label: 'Шаг резьбы (мм)',
                    rules: ['string', 'max:30'],
                    required: true,
                    options: ThreadPitchEnum::toArray(),
                ),
                new SelectField(
                    name: 'length',
                    label: 'Длина резьбы (мм)',
                    rules: ['string', 'max:30'],
                    required: true,
                    options: ThreadLengthEnum::toArray(),
                ),
            ],
            containerType: 'fieldset',
        );
    }

    private function createElectrodeField(): ObjectField
    {
        return new ObjectField(
            name: 'electrode',
            label: 'Электрод',
            rules: ['array'],
            children: [
                new SelectField(
                    name: 'gap',
                    label: 'Межконтактный зазор (мм)',
                    rules: ['string', 'max:30'],
                    required: true,
                    options: ElectrodeGapEnum::toArray(),
                ),
            ],
            containerType: 'fieldset',
        );
    }

    public function getName(): string
    {
        return 'Свечи зажигания';
    }

    public function getTemplateKey(): string
    {
        return 'sparkPlugs';
    }
}
