<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Обрабатывает основной лист книги ТС и сохраняет строки каталога автомобилей.
 */
final class VehicleMainSheetImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    /**
     * Получить ключи отчёта ошибок для листа ТС.
     *
     * Шаги:
     * 1) Принять ключ списка ошибок и ключ блокировки от многостраничного адаптера.
     * 2) Сохранить их в trait, чтобы ошибки всех листов попадали в один отчёт запуска.
     */
    public function __construct(
        string $cacheKey,
        string $lockKey,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * Обработать пачку строк основного листа ТС.
     *
     * Шаги:
     * 1) Лениво получить маппер строки и сервис сохранения после восстановления queued job.
     * 2) Для каждой строки собрать DTO и сохранить ТС внутри транзакции.
     * 3) Записать ошибку в cache-отчёт, если строка не прошла import validation.
     */
    public function collection(Collection $collection): void
    {
        $upsertVehicle = $this->upsertVehicle();
        $rowMapper = $this->rowMapper();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            try {
                DB::transaction(function () use ($rowMapper, $rowValues, $upsertVehicle): void {
                    $vehicleRow = $rowMapper->map($rowValues);

                    $upsertVehicle->upsertFromRow($vehicleRow);
                });
            } catch (ImportRowValidationException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Основная информация',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    )
                );
            }
        }
    }

    /**
     * Вернуть номер первой строки с данными на основном листе ТС.
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
     * Получить сервис сохранения ТС из строки листа.
     *
     * Шаги:
     * 1) Резолвить сервис из контейнера во время обработки queued job.
     * 2) Не хранить dependency graph в сериализованном Excel-адаптере.
     */
    private function upsertVehicle(): UpsertVehicleFromSheetServiceInterface
    {
        return app(UpsertVehicleFromSheetServiceInterface::class);
    }

    /**
     * Получить маппер основного листа ТС.
     *
     * Шаги:
     * 1) Резолвить маппер из контейнера во время обработки queued job.
     * 2) Использовать его для перевода сырых ячеек в DTO строки.
     */
    private function rowMapper(): VehicleSheetRowMapper
    {
        return app(VehicleSheetRowMapper::class);
    }
}
