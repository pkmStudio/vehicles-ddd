<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Services\NomenclatureDetailsDataPresenter;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\UnknownEnumValueException;
use Tests\TestCase;

/**
 * Проверяет selector presenter-ов номенклатурных details: headings, reference options, cells и
 * явные ошибки при неизвестных хранимых enum names.
 */
final class NomenclatureDetailsDataPresenterTest extends TestCase
{
    private NomenclatureDetailsDataPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->presenter = new NomenclatureDetailsDataPresenter;
    }

    /**
     * Проверяет `headingsFor(OIL_FILTER)`: заголовки включают поля фильтра и общий metrics-блок.
     */
    public function test_oil_filter_headings_include_filter_fields_and_metrics(): void
    {
        self::assertSame([
            'Исполнение фильтра',
            'Форма фильтра',
            'Корпус',
            'Резьба или Папа',
            'Диаметр (мм)',
            'Диаметр внешний уплотнителя (мм) или мама',
            'Длина (мм)',
            'Ширина (мм)',
            'Высота (мм)',
        ], $this->presenter->headingsFor(NomenclatureDetailTemplateEnum::OIL_FILTER));
    }

    /**
     * Проверяет `referenceOptionsFor(OIL_FILTER)`: условное поле собирает значения из двух
     * словарей, потому что stored `father` может быть резьбой или диаметром папы.
     */
    public function test_oil_filter_reference_options_merge_thread_and_father_dictionaries(): void
    {
        $options = $this->presenter->referenceOptionsFor(NomenclatureDetailTemplateEnum::OIL_FILTER);

        self::assertContains('M20x1.5', $options['Резьба или Папа']);
        self::assertContains('10', $options['Резьба или Папа']);
        self::assertContains('Накручиваемый фильтр', $options['Исполнение фильтра']);
    }

    /**
     * Проверяет `toExportCells(OIL_FILTER, ...)`: хранимые enum names переводятся в Excel labels,
     * а metrics-массивы рендерятся в `;`-joined ячейки.
     */
    public function test_oil_filter_cells_translate_stored_names_and_metrics(): void
    {
        $details = self::oilFilterDetails();

        self::assertSame(
            ['Накручиваемый фильтр', 'Цилиндр', 'Да', 'M20x1.5', 76, 62, '80;90', '70', '100'],
            $this->presenter->toExportCells(NomenclatureDetailTemplateEnum::OIL_FILTER, $details),
        );
    }

    /**
     * Проверяет `toExportCells(OIL_FILTER, ...)`: неизвестное хранимое имя не экспортируется
     * пустой ячейкой, а падает явной доменной ошибкой.
     */
    public function test_unknown_oil_filter_father_name_throws_instead_of_exporting_blank_cell(): void
    {
        $details = self::oilFilterDetails([
            'father' => 'UNKNOWN_FATHER',
        ]);

        $this->expectException(UnknownEnumValueException::class);

        $this->presenter->toExportCells(NomenclatureDetailTemplateEnum::OIL_FILTER, $details);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function oilFilterDetails(array $overrides = []): array
    {
        return [
            ...[
                'performance' => 'WIND_UP',
                'form' => 'CYLINDER',
                'frame' => true,
                'father' => 'M20X15',
                'diameter' => 76,
                'mother' => 62,
                'metrics' => [
                    'length' => [80, 90],
                    'width' => [70],
                    'height' => [100],
                ],
            ],
            ...$overrides,
        ];
    }
}
