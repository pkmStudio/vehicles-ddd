<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\OilFilterDetailsData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Проверяет правила обязательности details-шаблона номенклатуры `oilFilter`.
 */
final class NomenclatureOilFilterDetailsDataFactoryTest extends TestCase
{
    public function test_oil_filter_details_require_all_declared_fields_and_one_value_per_metric(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::OIL_FILTER,
            row: self::validRow(),
            index: $index,
        );

        self::assertInstanceOf(OilFilterDetailsData::class, $details);
        self::assertSame('WIND_UP', $details->performance);
        self::assertSame('CYLINDER', $details->form);
        self::assertTrue($details->frame);
        self::assertSame('M20X15', $details->father);
        self::assertSame(76, $details->diameter);
        self::assertSame(72, $details->mother);
        self::assertSame([100], $details->metrics->length);
        self::assertSame([76], $details->metrics->width);
        self::assertSame([76], $details->metrics->height);
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_oil_filter_details_require_every_field(int $emptyIndex, string $field): void
    {
        $index = 0;
        $row = self::validRow();
        $row[$emptyIndex] = null;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage("Поле «{$field}» обязательно для заполнения.");

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::OIL_FILTER,
            row: $row,
            index: $index,
        );
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'performance' => [0, 'Исполнение фильтра'],
            'form' => [1, 'Форма фильтра'],
            'frame' => [2, 'Корпус'],
            'father' => [3, 'Резьба или Папа'],
            'diameter' => [4, 'Диаметр'],
            'mother' => [5, 'Мать'],
            'metric length' => [6, 'Длина'],
            'metric width' => [7, 'Ширина'],
            'metric height' => [8, 'Высота'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function validRow(): array
    {
        return [
            'Накручиваемый фильтр',
            'Цилиндр',
            'Да',
            'M20x1.5',
            76,
            72,
            100,
            76,
            76,
        ];
    }
}
