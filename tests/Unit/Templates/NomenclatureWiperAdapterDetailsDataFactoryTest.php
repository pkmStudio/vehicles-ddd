<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Application\Services\NomenclatureDetailsDataPresenter;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Проверяет правила details-шаблона номенклатуры `wiperAdapter`.
 */
final class NomenclatureWiperAdapterDetailsDataFactoryTest extends TestCase
{
    public function test_wiper_adapter_details_do_not_include_construction_and_require_other_fields(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
            row: self::validRow(),
            index: $index,
        );

        self::assertInstanceOf(WiperAdapterDetailsData::class, $details);
        self::assertFalse(property_exists($details, 'construction'));
        self::assertSame('FRONT', $details->position);
        self::assertSame(['H'], $details->adapterTypeFront);
        self::assertSame([58], $details->metrics->length);
        self::assertSame([17], $details->metrics->width);
        self::assertSame([16], $details->metrics->height);
    }

    public function test_wiper_adapter_presenter_does_not_export_construction_column(): void
    {
        $presenter = app(NomenclatureDetailsDataPresenter::class);

        self::assertSame(
            ['Расположение', 'Тип крепления передних', 'Длина (мм)', 'Ширина (мм)', 'Высота (мм)'],
            $presenter->headingsFor(NomenclatureDetailTemplateEnum::WIPER_ADAPTER),
        );
        self::assertArrayNotHasKey(
            'Конструкция',
            $presenter->referenceOptionsFor(NomenclatureDetailTemplateEnum::WIPER_ADAPTER),
        );
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_wiper_adapter_details_require_every_remaining_field(int $emptyIndex, string $field): void
    {
        $index = 0;
        $row = self::validRow();
        $row[$emptyIndex] = null;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage("Поле «{$field}» обязательно для заполнения.");

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
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
            'position' => [0, 'Расположение'],
            'front adapter' => [1, 'Тип крепления передних'],
            'metric length' => [2, 'Длина'],
            'metric width' => [3, 'Ширина'],
            'metric height' => [4, 'Высота'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function validRow(): array
    {
        return [
            'Переднее',
            'Крючок (Hook / J-Hook)',
            58,
            17,
            16,
        ];
    }
}
