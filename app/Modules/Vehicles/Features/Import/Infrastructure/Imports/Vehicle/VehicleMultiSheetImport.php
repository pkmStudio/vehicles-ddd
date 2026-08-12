<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\VehicleImportSheet;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Запускает внешний многостраничный импорт ТС с основным листом и листом дворников.
 */
final class VehicleMultiSheetImport implements ShouldQueue, VehicleMultiSheetImportInterface, WithChunkReading, WithEvents, WithMultipleSheets
{
    public ImportRunContextDTO $context;

    /**
     * Запустить чтение книги ТС через Laravel Excel.
     *
     * Шаги:
     * 1) Сохранить контекст запуска, чтобы листы и событие завершения видели operation_id.
     * 2) Передать текущий объект Laravel Excel как многостраничный обработчик импорта.
     * 3) Прочитать файл с указанного диска или с диска по умолчанию.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void
    {
        $this->context = $context;
        Excel::import($this, $path, $disk);
    }

    /**
     * Собрать адаптеры листов книги ТС.
     *
     * Шаги:
     * 1) Рассчитать ключи кеша и блокировки отчёта ошибок по текущему operation_id.
     * 2) Создать адаптер основного листа ТС с теми же ключами.
     * 3) Создать адаптер листа дворников с теми же ключами.
     */
    public function sheets(): array
    {
        $cacheKey = $this->cacheKey();
        $lockKey = $this->lockKey();

        return [
            VehicleImportSheet::Main->value => app()->makeWith(
                VehicleMainSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
            VehicleImportSheet::Wipers->value => app()->makeWith(
                VehicleWipersSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
        ];
    }

    /**
     * Зарегистрировать сериализуемый обработчик завершения импорта.
     *
     * Шаги:
     * 1) Вернуть обработчик AfterImport как пару «класс/метод».
     * 2) Избежать замыкания, чтобы импорт в очереди оставался сериализуемым.
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    /**
     * Опубликовать доменное событие завершения импорта ТС.
     *
     * Шаги:
     * 1) Получить завершённый импорт из события Laravel Excel.
     * 2) Взять пользователя и operation_id из сохранённого контекста.
     * 3) Передать ключ кеша ошибок в событие завершения.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var VehicleMultiSheetImport $import */
        $import = $event->getConcernable();

        event(new VehicleImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey(),
            operationId: $import->context->operationId,
        ));
    }

    /**
     * Вернуть размер чанка для чтения листов ТС.
     *
     * Шаги:
     * 1) Зафиксировать размер чанка, подходящий для основного каталожного листа.
     * 2) Вернуть значение, которое Laravel Excel использует при чтении.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Рассчитать ключ кеш-хранилища ошибок импорта ТС.
     *
     * Шаги:
     * 1) Взять шаблон ключа из конфигурации импорта Vehicles.
     * 2) Подставить operation_id текущего запуска импорта.
     */
    private function cacheKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.vehicle_import_failures'),
            $this->context->operationId,
        );
    }

    /**
     * Рассчитать ключ блокировки кеш-хранилища ошибок импорта ТС.
     *
     * Шаги:
     * 1) Взять шаблон ключа блокировки из конфигурации импорта Vehicles.
     * 2) Подставить operation_id текущего запуска импорта.
     */
    private function lockKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.vehicle_import_failures_lock'),
            $this->context->operationId,
        );
    }
}
