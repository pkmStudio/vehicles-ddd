<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Templates;

use App\Templates\Application\Services\DetailsDataPresenter;
use App\Templates\Domain\Enums\DetailTemplateEnum;
use Tests\TestCase;

/**
 * `DetailsDataPresenter` — обратная сторона `DetailsDataFactory` (см. `DetailsDataFactoryTest`):
 * рендерит уже сохранённые details в плоский набор Excel-ячеек экспорта. Раньше эта логика жила
 * на самих `<X>DetailsData::toExportCells()`/`::headings()`, теперь `Data`-классы — чистые
 * объекты-значения, вся логика — здесь.
 */
final class DetailsDataPresenterTest extends TestCase
{
    private DetailsDataPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new DetailsDataPresenter;
    }

    /**
     * Проверяет `headingsFor(SPARK_PLUGS)`: заголовки идут плоским списком в фиксированном
     * порядке (резьба → электрод → ширина зева ключа).
     *
     * Шаги:
     * 1. Зовёт `headingsFor(DetailTemplateEnum::SPARK_PLUGS)`.
     * 2. Проверяет точный порядок и состав всех 5 заголовков.
     */
    public function test_spark_plugs_headings_are_ordered_and_flat(): void
    {
        $this->assertSame([
            'Размер резьбы',
            'Шаг резьбы (мм)',
            'Длина резьбы (мм)',
            'Межконтактный зазор (мм)',
            'Ширина зева гаечного ключа (мм)',
        ], $this->presenter->headingsFor(DetailTemplateEnum::SPARK_PLUGS));
    }

    /**
     * Проверяет `toExportCells(SPARK_PLUGS, ...)`: хранимые имена (`case->name`) корректно
     * переводятся назад в Excel-лейблы (`case->value`) — обратное направление к
     * `DetailsDataFactoryTest::test_builds_spark_plugs_details_translating_labels_to_stored_names()`.
     *
     * Шаги:
     * 1. Собирает `$details` с хранимыми именами (`M14X125`/`TP125`/`TL19`/`G09`/`WJ19`).
     * 2. Зовёт `toExportCells(SPARK_PLUGS, $details)`.
     * 3. Проверяет, что результат — те же 5 лейблов, что и на входе исходной Excel-строки.
     */
    public function test_spark_plugs_cells_translate_stored_names_back_to_labels(): void
    {
        $details = [
            'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
            'electrode' => ['gap' => 'G09'],
            'wrench_jaw_width' => 'WJ19',
        ];

        $this->assertSame(
            ['M14x1.25', '1.25', '19', '0.9', '19'],
            $this->presenter->toExportCells(DetailTemplateEnum::SPARK_PLUGS, $details),
        );
    }

    /**
     * Проверяет `headingsFor(WIPER)`: количество заголовков совпадает с числом колонок,
     * которые реально читает `DetailsDataFactory` (6 передних + 4 задних = 10).
     *
     * Шаги:
     * 1. Зовёт `headingsFor(DetailTemplateEnum::WIPER)`.
     * 2. Проверяет, что заголовков ровно 10.
     */
    public function test_wiper_headings_count_matches_columns_consumed(): void
    {
        $this->assertCount(10, $this->presenter->headingsFor(DetailTemplateEnum::WIPER));
    }

    /**
     * Проверяет `toExportCells(WIPER, ...)`: экспорт читает уже смерженный
     * `WiperSpecificationService::mergeForExport()`-массив, где отсутствующей стороны нет вовсе
     * — отсутствующая сторона должна дать пустые/null-ячейки, а не сократить итоговый набор.
     *
     * Шаги:
     * 1. Собирает `$details` только с `front`-стороной (ключа `back` в массиве вообще нет).
     * 2. Зовёт `toExportCells(WIPER, $details)`.
     * 3. Проверяет, что передние ячейки содержат реальные значения, а задние — null/пустая строка.
     */
    public function test_wiper_cells_fill_missing_side_with_blanks(): void
    {
        $details = [
            'front' => [
                'length_main' => ['min' => 500, 'max' => 550],
                'length_second' => ['min' => null, 'max' => null],
                'adapter_type_front' => ['H'],
                'count_wipers' => 2,
            ],
            // 'back' отсутствует вовсе — как после WiperSpecificationService::mergeForExport()
            // с пустым $backData.
        ];

        $this->assertSame(
            [500, 550, null, null, 'Крючок (Hook / J-Hook)', 2, null, null, '', null],
            $this->presenter->toExportCells(DetailTemplateEnum::WIPER, $details),
        );
    }

    /**
     * Проверяет `toExportCells(WIPER, ...)`: массив хранимых имён (`case->name`) многозначного
     * адаптера переводится обратно в `;`-джойн строку Excel-лейблов.
     *
     * Шаги:
     * 1. Собирает `$details` с двумя хранимыми именами в `adapter_type_front` (`H`, `S`).
     * 2. Зовёт `toExportCells(WIPER, $details)`.
     * 3. Проверяет, что ячейка типа крепления — `;`-джойн строка из двух русских лейблов.
     */
    public function test_wiper_multiple_adapters_round_trip_as_semicolon_joined_labels(): void
    {
        $details = ['front' => ['adapter_type_front' => ['H', 'S']]];

        $cells = $this->presenter->toExportCells(DetailTemplateEnum::WIPER, $details);

        $this->assertSame('Крючок (Hook / J-Hook);Боковой штырь (Side pin)', $cells[4]);
    }
}
