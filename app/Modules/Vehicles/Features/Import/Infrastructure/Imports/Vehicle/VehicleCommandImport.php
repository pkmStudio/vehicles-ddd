<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleTdRowMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер командного импорта ТС: читает файл и передаёт строки сервису записи.
 */
final class VehicleCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, VehicleCommandImportInterface, WithChunkReading, WithEvents, WithStartRow
{
    private ?UpsertVehicleFromTdRowServiceInterface $service = null;

    private ?VehicleTdRowMapper $rowMapper = null;

    /**
     * Запустить командный импорт ТС.
     *
     * Шаги:
     * 1) Передать текущий адаптер в Laravel Excel.
     * 2) Прочитать файл по переданному пути.
     */
    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    /**
     * Вернуть размер чанка для командного импорта ТС.
     *
     * Шаги:
     * 1) Зафиксировать размер пачки для построчной записи.
     * 2) Вернуть значение, которое использует Laravel Excel.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Обработать пачку строк командного импорта ТС.
     *
     * Шаги:
     * 1) Получить сервис записи ТС и маппер TecDoc-строки из контейнера.
     * 2) Сохранить ТС или получить ошибку строки от маппера/сервиса.
     * 3) Передать ошибки строки в общий обработчик Laravel Excel.
     */
    public function collection(Collection $collection): void
    {
        $service = $this->service();
        $rowMapper = $this->rowMapper();

        foreach ($collection as $index => $row) {
            $line = $index + $this->startRow();
            $rowValues = $row->toArray();
            try {
                $vehicleRow = $rowMapper->map($rowValues);
                $service->upsertFromRow($vehicleRow);
            } catch (ImportRowValidationException|ImportRowReferenceNotFoundException $e) {
                $this->onFailure(new Failure($line, 'ТС', $e->errors(), $rowValues));
            }
        }
    }

    /**
     * Записать ошибки командного импорта ТС в лог ошибок.
     *
     * Шаги:
     * 1) Пройти по всем ошибкам, которые вернул Laravel Excel или код импорта.
     * 2) Записать номер строки, атрибут, ошибки и исходные значения.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Vehicle import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Вернуть номер первой строки данных командного импорта ТС.
     *
     * Шаги:
     * 1) Пропустить строку заголовков Excel.
     * 2) Начать обработку со второй строки.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Зарегистрировать событие завершения командного импорта ТС.
     *
     * Шаги:
     * 1) Вернуть обработчик AfterImport как сериализуемую пару «класс/метод».
     * 2) Не использовать замыкание внутри импорта в очереди.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать доменное событие завершения командного импорта ТС.
     *
     * Шаги:
     * 1) Создать факт VehicleCommandImported.
     * 2) Отправить его через события Laravel.
     */
    public static function afterImport(): void
    {
        event(new VehicleCommandImported);
    }

    /**
     * Получить сервис сохранения ТС из TecDoc-строки.
     *
     * Шаги:
     * 1) Лениво получить сервис из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function service(): UpsertVehicleFromTdRowServiceInterface
    {
        return $this->service ??= app(UpsertVehicleFromTdRowServiceInterface::class);
    }

    /**
     * Получить маппер TecDoc-строки ТС.
     *
     * Шаги:
     * 1) Лениво получить маппер из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function rowMapper(): VehicleTdRowMapper
    {
        return $this->rowMapper ??= app(VehicleTdRowMapper::class);
    }
}
