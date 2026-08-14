<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Export;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Feature-тест на VehicleMultiSheetExport: реальные Repository/Row/Expander/DetailsBuilder,
 * настоящая БД (Postgres, см. phpunit.xml), настоящая генерация xlsx — файл читается обратно
 * и проверяется по содержимому. До этой сессии у Export не было ни одного автотеста (сначала
 * потому что фича была мёртвым кодом без единой точки входа, потом стала реально вызываемой
 * через RabbitMQ — см. refactor-export.md).
 */
final class VehicleMultiSheetExportTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicle(Manufacturer $manufacturer, int $msId, string $name, bool $isAllow): Vehicle
    {
        return Vehicle::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'mfa_id' => $manufacturer->mfa_id,
            'ms_id' => $msId,
            'name' => $name,
            'generation' => 'III',
            'generation_year_from' => 2013,
            'type' => 'PC',
            'type_carcase' => 'Hatchback',
            'provider' => ProviderEnum::TD->value,
            'steering_type' => 'Левый руль',
            'is_allow' => $isAllow,
        ]);
    }

    /**
     * @return array{0: array<int, array<int, mixed>>, 1: array<int, array<int, mixed>>}
     */
    private function readSheets(string $path): array
    {
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        return [
            $spreadsheet->getSheet(0)->toArray(),
            $spreadsheet->getSheet(1)->toArray(),
        ];
    }

    /**
     * Проверяет, что isAllow=false (дефолт) выгружает полный каталог — обе машины,
     * допущенную и недопущенную.
     *
     * Шаги:
     * 1. Создаёт производителя и два ТС: одно с is_allow=true, другое с is_allow=false.
     * 2. Резолвит VehicleMultiSheetExportInterface с isAllow=false и зовёт export().
     * 3. Читает основной лист сгенерированного файла через PhpSpreadsheet.
     * 4. Проверяет, что в файле заголовок + обе строки (обе модели присутствуют).
     */
    public function test_exports_main_sheet_with_all_vehicles_when_is_allow_false(): void
    {
        Storage::fake('local');

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $this->createVehicle($manufacturer, 300, 'Octavia', true);
        $this->createVehicle($manufacturer, 301, 'Fabia', false);

        $context = new ExportRunContextDTO(userId: 1, operationId: 'vehicle-export-full');

        /** @var VehicleMultiSheetExportInterface $export */
        $export = app()->makeWith(VehicleMultiSheetExportInterface::class, ['isAllow' => false]);
        $path = $export->export($context, 'local');

        Storage::disk('local')->assertExists($path);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        [$mainRows] = $this->readSheets($path);

        $this->assertSame(3, $spreadsheet->getSheetCount());
        $this->assertSame('Основная информация', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Дворники', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('Справочники', $spreadsheet->getSheet(2)->getTitle());
        $this->assertTrue($spreadsheet->getSheet(0)->getStyle('A1')->getFont()->getBold());
        $this->assertContains('Тип кузова', $spreadsheet->getSheet(2)->toArray()[0]);
        $this->assertContains('Тип крепления передних', $spreadsheet->getSheet(2)->toArray()[0]);
        $this->assertCount(3, $mainRows); // заголовок + 2 строки
        $names = array_column(array_slice($mainRows, 1), 4); // индекс 4 = "Модель"
        $this->assertEqualsCanonicalizing(['Octavia', 'Fabia'], $names);
    }

    /**
     * Проверяет бизнес-фильтр isAllow=true: в выгрузку попадают только допущенные ТС.
     *
     * Шаги:
     * 1. Создаёт производителя и два ТС: одно с is_allow=true, другое с is_allow=false.
     * 2. Резолвит VehicleMultiSheetExportInterface с isAllow=true и зовёт export().
     * 3. Читает основной лист сгенерированного файла.
     * 4. Проверяет, что в файле заголовок + ровно одна строка — допущенная модель.
     */
    public function test_exports_only_allowed_vehicles_when_is_allow_true(): void
    {
        Storage::fake('local');

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $this->createVehicle($manufacturer, 300, 'Octavia', true);
        $this->createVehicle($manufacturer, 301, 'Fabia', false);

        $context = new ExportRunContextDTO(userId: 1, operationId: 'vehicle-export-filtered');

        /** @var VehicleMultiSheetExportInterface $export */
        $export = app()->makeWith(VehicleMultiSheetExportInterface::class, ['isAllow' => true]);
        $path = $export->export($context, 'local');

        [$mainRows] = $this->readSheets($path);

        $this->assertCount(2, $mainRows); // заголовок + 1 строка
        $this->assertSame('Octavia', $mainRows[1][4]);
    }

    /**
     * Регрессионный тест на баг из refactor-export.md: backed enum (CarcaseTypeEnum,
     * VehicleTypeEnum, SteeringTypeEnum, ProviderEnum) клался в строку экспорта без ->value —
     * PhpSpreadsheet не мог сериализовать объект и падал. Плюс проверяет, что лист дворников
     * реально собирает данные PartSpecification.
     *
     * Шаги:
     * 1. Создаёт ТС и PartSpecification с шаблоном WIPER (front-сторона).
     * 2. Зовёт export() и читает лист дворников (второй лист) сгенерированного файла.
     * 3. Проверяет, что строка реально записалась (не упало на сериализации enum) и что
     *    значения enum-полей — строки ('Hatchback'/'PC'/'Левый руль'), а не объекты.
     */
    public function test_exports_wiper_sheet_without_failing_on_backed_enum_fields(): void
    {
        Storage::fake('local');

        $manufacturer = Manufacturer::query()->create(['mfa_id' => 10, 'name' => 'Skoda', 'provider' => 'TD']);
        $vehicle = $this->createVehicle($manufacturer, 300, 'Octavia', true);

        PartSpecification::query()->create([
            'partable_type' => PartableTypeEnum::VEHICLE->value,
            'partable_id' => $vehicle->id,
            'template' => DetailTemplateEnum::WIPER->value,
            'details' => [
                'front' => [
                    'length_main' => ['min' => 500, 'max' => 550],
                    'length_second' => ['min' => 450, 'max' => 500],
                    'adapter_type_front' => ['A1'],
                    'count_wipers' => 2,
                ],
            ],
        ]);

        $context = new ExportRunContextDTO(userId: 1, operationId: 'vehicle-export-wiper');

        /** @var VehicleMultiSheetExportInterface $export */
        $export = app()->makeWith(VehicleMultiSheetExportInterface::class, ['isAllow' => false]);
        $path = $export->export($context, 'local');

        [, $wiperRows] = $this->readSheets($path);

        $this->assertCount(2, $wiperRows); // заголовок + 1 строка
        $this->assertSame('Hatchback', $wiperRows[1][10]);
        $this->assertSame('PC', $wiperRows[1][11]);
        $this->assertSame('Левый руль', $wiperRows[1][14]);
    }
}
