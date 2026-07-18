<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Templates;

use App\Modules\Templates\Application\Factories\DetailsDataFactory;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Tests\TestCase;

/**
 * Раньше label↔name перевод (Excel-лейбл ↔ хранимый `case->name`) не имел собственного
 * юнит-теста — он был спрятан внутри генерического `DetailsBuilder`/`ExportDetailsBuilder`, потом
 * — в статических `<X>DetailsData::fromImportRow()`. Теперь вся сборка из строки — на
 * `DetailsDataFactory`, здесь и тестируется.
 */
final class DetailsDataFactoryTest extends TestCase
{
    private DetailsDataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DetailsDataFactory;
    }

    /**
     * Проверяет `buildFromRow()` для свечи зажигания: те же 5 ячеек, что использует
     * `EngineSparkPlugsSheetImportTest`, дают тот же хранимый массив (`case->name`), что и
     * старый `DetailsBuilder` — эталон синхронизирован с тем feature-тестом.
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(SPARK_PLUGS, ...)` со строкой `['M14x1.25', '1.25', '19', '0.9', '19']`.
     * 2. Проверяет, что результат — вложенный массив хранимых имён (`M14X125`/`TP125`/`TL19`/`G09`/`WJ19`).
     * 3. Проверяет, что `$index` сдвинулся ровно на 5 (по числу прочитанных ячеек).
     */
    public function test_builds_spark_plugs_details_translating_labels_to_stored_names(): void
    {
        $row = ['M14x1.25', '1.25', '19', '0.9', '19'];
        $index = 0;

        $details = $this->factory->buildFromRow(DetailTemplateEnum::SPARK_PLUGS, $row, $index)->toArray();

        $this->assertSame([
            'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
            'electrode' => ['gap' => 'G09'],
            'wrench_jaw_width' => 'WJ19',
        ], $details);
        $this->assertSame(5, $index);
    }

    /**
     * Проверяет `buildFromRow()` для свечи зажигания: пустые/отсутствующие ячейки — не ошибка,
     * а null в соответствующих полях.
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(SPARK_PLUGS, ...)` со строкой `[null, '', '19']`.
     * 2. Проверяет, что `size`/`pitch`/`gap`/`wrench_jaw_width` стали null, а `length` — `'TL19'`.
     */
    public function test_spark_plugs_missing_cells_produce_nulls_without_throwing(): void
    {
        $row = [null, '', '19'];
        $index = 0;

        $details = $this->factory->buildFromRow(DetailTemplateEnum::SPARK_PLUGS, $row, $index)->toArray();

        $this->assertSame([
            'thread' => ['size' => null, 'pitch' => null, 'length' => 'TL19'],
            'electrode' => ['gap' => null],
            'wrench_jaw_width' => null,
        ], $details);
    }

    /**
     * Проверяет `buildFromRow()`: нераспознанный лейбл — явное исключение (как раньше
     * `DetailsBuilder::getVarKey()`), а не тихий null.
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(SPARK_PLUGS, ...)` со строкой с несуществующим значением размера резьбы.
     * 2. Проверяет, что выбрасывается `RuntimeException`.
     */
    public function test_unknown_label_throws(): void
    {
        $row = ['НЕИЗВЕСТНЫЙ РАЗМЕР'];
        $index = 0;

        $this->expectException(\RuntimeException::class);

        $this->factory->buildFromRow(DetailTemplateEnum::SPARK_PLUGS, $row, $index);
    }

    /**
     * Проверяет `buildFromRow()` для дворников: импорт всегда строит ОБЕ стороны (10 колонок
     * подряд), даже если реально заполнена только одна — фильтрация по факту заполненности
     * остаётся заботой `WiperSpecificationService::splitDetails()`, не фабрики.
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(WIPER, ...)` со строкой, где заполнены и front, и back.
     * 2. Проверяет, что результат содержит оба корневых ключа (`front`/`back`) с ожидаемыми полями.
     * 3. Проверяет, что `$index` сдвинулся ровно на 10 (по числу прочитанных ячеек).
     */
    public function test_builds_wiper_details_for_both_sides(): void
    {
        $row = [
            500, 550, 450, 500, 'Крючок (Hook / J-Hook)', 2,
            400, 420, 'RA', 1,
        ];
        $index = 0;

        $details = $this->factory->buildFromRow(DetailTemplateEnum::WIPER, $row, $index)->toArray();

        $this->assertSame([
            'front' => [
                'length_main' => ['min' => 500, 'max' => 550],
                'length_second' => ['min' => 450, 'max' => 500],
                'adapter_type_front' => ['H'],
                'count_wipers' => 2,
            ],
            'back' => [
                'length_rear' => ['min' => 400, 'max' => 420],
                'adapter_type_rear' => ['RA'],
                'count_wipers' => 1,
            ],
        ], $details);
        $this->assertSame(10, $index);
    }

    /**
     * Проверяет `buildFromRow()` для дворников: пустые ячейки по всей строке — не ошибка, все
     * листья становятся null/[].
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(WIPER, ...)` со строкой из 10 null-ячеек.
     * 2. Проверяет, что все скалярные поля — null, а массивы адаптеров — пустые.
     */
    public function test_wiper_handles_fully_blank_row(): void
    {
        $row = array_fill(0, 10, null);
        $index = 0;

        $details = $this->factory->buildFromRow(DetailTemplateEnum::WIPER, $row, $index)->toArray();

        $this->assertSame([
            'front' => [
                'length_main' => ['min' => null, 'max' => null],
                'length_second' => ['min' => null, 'max' => null],
                'adapter_type_front' => [],
                'count_wipers' => null,
            ],
            'back' => [
                'length_rear' => ['min' => null, 'max' => null],
                'adapter_type_rear' => [],
                'count_wipers' => null,
            ],
        ], $details);
    }

    /**
     * Проверяет `buildFromRow()` для дворников: многозначный `;`-джойн лейбл адаптера
     * разбирается в массив хранимых имён, а не остаётся одной строкой.
     *
     * Шаги:
     * 1. Зовёт `buildFromRow(WIPER, ...)` со строкой, где ячейка адаптера — два лейбла через `;`.
     * 2. Проверяет, что `adapter_type_front` стал массивом из двух хранимых имён (`H`, `S`).
     */
    public function test_wiper_multiple_adapters_parsed_from_semicolon_joined_labels(): void
    {
        $row = [null, null, null, null, 'Крючок (Hook / J-Hook);Боковой штырь (Side pin)', null, null, null, null, null];
        $index = 0;

        $details = $this->factory->buildFromRow(DetailTemplateEnum::WIPER, $row, $index)->toArray();

        $this->assertSame(['H', 'S'], $details['front']['adapter_type_front']);
    }
}
