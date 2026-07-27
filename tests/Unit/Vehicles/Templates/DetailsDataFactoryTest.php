<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Templates;

use App\Modules\Templates\Application\Factories\DetailsDataFactory;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
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
     * Проверяет `make()` для свечи зажигания: те же 5 ячеек, что использует
     * `EngineSparkPlugsSheetImportTest`, дают тот же хранимый массив (`case->name`), что и
     * старый `DetailsBuilder` — эталон синхронизирован с тем feature-тестом.
     *
     * Шаги:
     * 1. Зовёт `make(SPARK_PLUGS, ...)` со строкой `['M14x1.25', '1.25', '19', '0.9', '19']`.
     * 2. Проверяет, что результат — вложенный массив хранимых имён (`M14X125`/`TP125`/`TL19`/`G09`/`WJ19`).
     * 3. Проверяет, что `$index` сдвинулся ровно на 5 (по числу прочитанных ячеек).
     */
    public function test_builds_spark_plugs_details_translating_labels_to_stored_names(): void
    {
        $row = ['M14x1.25', '1.25', '19', '0.9', '19'];
        $index = 0;

        $details = $this->factory->make(DetailTemplateEnum::SPARK_PLUGS, $row, $index)->toArray();

        $this->assertSame([
            'thread' => ['size' => 'M14X125', 'pitch' => 'TP125', 'length' => 'TL19'],
            'electrode' => ['gap' => 'G09'],
            'wrench_jaw_width' => 'WJ19',
        ], $details);
        $this->assertSame(5, $index);
    }

    public function test_spark_plugs_missing_required_cells_throw(): void
    {
        $row = [null, '', '19'];
        $index = 0;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage('Поле «Размер резьбы» обязательно для заполнения.');

        $this->factory->make(DetailTemplateEnum::SPARK_PLUGS, $row, $index);
    }

    /**
     * Проверяет `make()`: нераспознанный лейбл — явное исключение (как раньше
     * `DetailsBuilder::getVarKey()`), а не тихий null.
     *
     * Шаги:
     * 1. Зовёт `make(SPARK_PLUGS, ...)` со строкой с несуществующим значением размера резьбы.
     * 2. Проверяет, что выбрасывается доменная ошибка сборки details.
     */
    public function test_unknown_label_throws(): void
    {
        $row = ['НЕИЗВЕСТНЫЙ РАЗМЕР'];
        $index = 0;

        $this->expectException(DetailsDataBuildException::class);

        $this->factory->make(DetailTemplateEnum::SPARK_PLUGS, $row, $index);
    }

    /**
     * Проверяет `make()` для дворников: импорт всегда строит ОБЕ стороны (10 колонок
     * подряд), даже если реально заполнена только одна — фильтрация по факту заполненности
     * остаётся заботой `WiperSpecificationService::splitDetails()`, не фабрики.
     *
     * Шаги:
     * 1. Зовёт `make(WIPER, ...)` со строкой, где заполнены и front, и back.
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

        $details = $this->factory->make(DetailTemplateEnum::WIPER, $row, $index)->toArray();

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

    public function test_wiper_blank_required_cells_throw(): void
    {
        $row = array_fill(0, 10, null);
        $index = 0;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage('Поле «Минимальная длина щётки» обязательно для заполнения.');

        $this->factory->make(DetailTemplateEnum::WIPER, $row, $index);
    }

    /**
     * Проверяет `make()` для дворников: многозначный `;`-джойн лейбл адаптера
     * разбирается в массив хранимых имён, а не остаётся одной строкой.
     *
     * Шаги:
     * 1. Зовёт `make(WIPER, ...)` со строкой, где ячейка адаптера — два лейбла через `;`.
     * 2. Проверяет, что `adapter_type_front` стал массивом из двух хранимых имён (`H`, `S`).
     */
    public function test_wiper_multiple_adapters_parsed_from_semicolon_joined_labels(): void
    {
        $row = [
            500, 550, 450, 500, 'Крючок (Hook / J-Hook);Боковой штырь (Side pin)', 2,
            400, 420, 'RA', 1,
        ];
        $index = 0;

        $details = $this->factory->make(DetailTemplateEnum::WIPER, $row, $index)->toArray();

        $this->assertSame(['H', 'S'], $details['front']['adapter_type_front']);
    }
}
