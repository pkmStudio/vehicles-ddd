<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\EngineImportSheet;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Запускает внешний многостраничный импорт двигателей с основным листом и листом свечей.
 */
final class EngineMultiSheetImport implements EngineMultiSheetImportInterface, ShouldQueue, WithChunkReading, WithEvents, WithMultipleSheets
{
    public ImportRunContextDTO $context;

    /**
     * Запустить чтение книги двигателей через Laravel Excel.
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
     * Собрать адаптеры листов книги двигателей.
     *
     * Шаги:
     * 1) Рассчитать ключи кеша и блокировки отчёта ошибок по текущему operation_id.
     * 2) Создать адаптер основного листа двигателей с теми же ключами.
     * 3) Создать адаптер листа свечей с теми же ключами.
     */
    public function sheets(): array
    {
        $cacheKey = $this->cacheKey();
        $lockKey = $this->lockKey();

        return [
            EngineImportSheet::Main->value => app()->makeWith(
                EngineMainSheetImport::class,
                ['cacheKey' => $cacheKey, 'lockKey' => $lockKey],
            ),
            EngineImportSheet::SparkPlugs->value => app()->makeWith(
                EngineSparkPlugsSheetImport::class,
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
     * Опубликовать доменное событие завершения импорта двигателей.
     *
     * Шаги:
     * 1) Получить завершённый импорт из события Laravel Excel.
     * 2) Взять пользователя и operation_id из сохранённого контекста.
     * 3) Передать ключ кеша ошибок в событие завершения.
     */
    public static function afterImport(AfterImport $event): void
    {
        /** @var EngineMultiSheetImport $import */
        $import = $event->getConcernable();

        event(new EngineImportCompleted(
            userId: $import->context->userId,
            cacheKey: $import->cacheKey(),
            operationId: $import->context->operationId,
        ));
    }

    /**
     * Вернуть размер чанка для чтения листов двигателей.
     *
     * Шаги:
     * 1) Зафиксировать небольшой размер чанка для построчной обработки.
     * 2) Вернуть значение, которое Laravel Excel использует при чтении.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Рассчитать ключ кеш-хранилища ошибок импорта двигателей.
     *
     * Шаги:
     * 1) Взять шаблон ключа из конфигурации импорта Vehicles.
     * 2) Подставить operation_id текущего запуска импорта.
     */
    private function cacheKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures'),
            $this->context->operationId,
        );
    }

    /**
     * Рассчитать ключ блокировки кеш-хранилища ошибок импорта двигателей.
     *
     * Шаги:
     * 1) Взять шаблон ключа блокировки из конфигурации импорта Vehicles.
     * 2) Подставить operation_id текущего запуска импорта.
     */
    private function lockKey(): string
    {
        return sprintf(
            (string) config('vehicles.import.failures.cache.keys.engine_import_failures_lock'),
            $this->context->operationId,
        );
    }
}
