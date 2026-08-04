<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;
use Tests\TestCase;

/**
 * Проверяет правила обязательности details-шаблона номенклатуры `brakePads`.
 */
final class NomenclatureBrakePadDetailsDataFactoryTest extends TestCase
{
    public function test_brake_pad_details_require_all_declared_fields_and_one_value_per_metric(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::BRAKE_PADS,
            row: [
                'Переднее',
                'Дисковые',
                'Безасбестовые',
                150,
                60,
                18,
            ],
            index: $index,
        );

        self::assertInstanceOf(BrakePadDetailsData::class, $details);
        self::assertSame('FRONT', $details->position);
        self::assertSame('DISK', $details->brakePadsType);
        self::assertSame('ASBESTOS_FREE', $details->materialLinings);
        self::assertSame([150], $details->metrics->length);
        self::assertSame([60], $details->metrics->width);
        self::assertSame([18], $details->metrics->height);
    }

    public function test_brake_pad_details_require_length_metric(): void
    {
        $this->expectMissingMetric('Длина', [
            'Переднее',
            'Дисковые',
            'Безасбестовые',
            null,
            60,
            18,
        ]);
    }

    public function test_brake_pad_details_require_width_metric(): void
    {
        $this->expectMissingMetric('Ширина', [
            'Переднее',
            'Дисковые',
            'Безасбестовые',
            150,
            null,
            18,
        ]);
    }

    public function test_brake_pad_details_require_height_metric(): void
    {
        $this->expectMissingMetric('Высота', [
            'Переднее',
            'Дисковые',
            'Безасбестовые',
            150,
            60,
            null,
        ]);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function expectMissingMetric(string $field, array $row): void
    {
        $index = 0;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage("Поле «{$field}» обязательно для заполнения.");

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::BRAKE_PADS,
            row: $row,
            index: $index,
        );
    }
}
