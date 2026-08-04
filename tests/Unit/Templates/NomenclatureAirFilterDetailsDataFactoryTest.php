<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Проверяет правила обязательности details-шаблона номенклатуры `airFilter`.
 */
final class NomenclatureAirFilterDetailsDataFactoryTest extends TestCase
{
    public function test_air_filter_details_require_all_declared_fields_and_one_value_per_metric(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::AIR_FILTER,
            row: self::validRow(),
            index: $index,
        );

        self::assertInstanceOf(AirFilterDetailsData::class, $details);
        self::assertSame('LONG_TERM', $details->performance);
        self::assertSame('RECTANGLE', $details->form);
        self::assertTrue($details->frame);
        self::assertSame('DUST', $details->filterType);
        self::assertSame([250], $details->metrics->length);
        self::assertSame([180], $details->metrics->width);
        self::assertSame([40], $details->metrics->height);
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_air_filter_details_require_every_field(int $emptyIndex, string $field): void
    {
        $index = 0;
        $row = self::validRow();
        $row[$emptyIndex] = null;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage("Поле «{$field}» обязательно для заполнения.");

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::AIR_FILTER,
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
            'filter type' => [3, 'Вид фильтра'],
            'metric length' => [4, 'Длина'],
            'metric width' => [5, 'Ширина'],
            'metric height' => [6, 'Высота'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function validRow(): array
    {
        return [
            'Долговременный фильтр',
            'Прямоугольник',
            'Да',
            'Пылевой',
            250,
            180,
            40,
        ];
    }
}
