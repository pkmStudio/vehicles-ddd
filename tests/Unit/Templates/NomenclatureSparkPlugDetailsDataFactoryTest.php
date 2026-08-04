<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Проверяет правила обязательности details-шаблона номенклатуры `sparkPlugs`.
 */
final class NomenclatureSparkPlugDetailsDataFactoryTest extends TestCase
{
    public function test_spark_plug_details_require_all_declared_fields_and_one_value_per_metric(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::SPARK_PLUGS,
            row: self::validRow(),
            index: $index,
        );

        self::assertInstanceOf(SparkPlugDetailsData::class, $details);
        self::assertSame('M14X125', $details->thread->size);
        self::assertSame('TP125', $details->thread->pitch);
        self::assertSame('TL19', $details->thread->length);
        self::assertSame('G1', $details->electrode->gap);
        self::assertSame('ONE', $details->electrode->countSide);
        self::assertSame('WJ16', $details->wrenchJawWidth);
        self::assertSame(20.0, $details->tighteningTorque->min);
        self::assertSame(30.0, $details->tighteningTorque->max);
        self::assertSame([50], $details->metrics->length);
        self::assertSame([14], $details->metrics->width);
        self::assertSame([19], $details->metrics->height);
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_spark_plug_details_require_every_field(int $emptyIndex, string $field): void
    {
        $index = 0;
        $row = self::validRow();
        $row[$emptyIndex] = null;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage("Поле «{$field}» обязательно для заполнения.");

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::SPARK_PLUGS,
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
            'thread size' => [0, 'Размер резьбы'],
            'thread pitch' => [1, 'Шаг резьбы'],
            'thread length' => [2, 'Длина резьбы'],
            'electrode gap' => [3, 'Межконтактный зазор'],
            'electrode side count' => [4, 'Число боковых электродов'],
            'wrench jaw width' => [5, 'Ширина зева гаечного ключа'],
            'tightening torque min' => [6, 'Минимальный момент затяжки'],
            'tightening torque max' => [7, 'Максимальный момент затяжки'],
            'metric length' => [8, 'Длина'],
            'metric width' => [9, 'Ширина'],
            'metric height' => [10, 'Высота'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function validRow(): array
    {
        return [
            'M14x1.25',
            '1.25',
            '19',
            '1',
            '1',
            '16',
            20,
            30,
            50,
            14,
            19,
        ];
    }
}
