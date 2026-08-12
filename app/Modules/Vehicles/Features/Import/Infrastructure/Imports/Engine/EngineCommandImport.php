<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers\EngineSheetRowMapper;
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
 * Excel-адаптер импорта двигателей: читает файл по чанкам и передаёт строки сервису сохранения.
 */
final class EngineCommandImport implements EngineCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    private ?UpsertEngineFromSheetServiceInterface $service = null;

    private ?EngineSheetRowMapper $rowMapper = null;

    /**
     * Получить зависимости для прямого запуска импорта двигателей.
     *
     * Шаги:
     * 1) Принять сервис сохранения двигателя из строки.
     * 2) Принять маппер строки командного листа двигателей.
     * 3) Сохранить зависимости до сериализации задания очереди.
     */
    public function __construct(
        UpsertEngineFromSheetServiceInterface $service,
        EngineSheetRowMapper $rowMapper,
    ) {
        $this->service = $service;
        $this->rowMapper = $rowMapper;
    }

    /**
     * Подготовить импорт к сериализации в очередь.
     *
     * Шаги:
     * 1) Не сохранять сервис записи двигателя.
     * 2) Не сохранять маппер строки.
     * 3) Оставить импорт в очереди сериализуемым без графа зависимостей.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * Восстановить импорт после очереди.
     *
     * Шаги:
     * 1) Сбросить сервис записи двигателя.
     * 2) Сбросить маппер строки.
     * 3) Позволить методам ленивого получения зависимостей обратиться к контейнеру при обработке.
     *
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->service = null;
        $this->rowMapper = null;
    }

    /**
     * Запустить импорт файла двигателей.
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
     * Вернуть размер чанка для командного импорта двигателей.
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
     * Обработать пачку строк командного импорта двигателей.
     *
     * Шаги:
     * 1) Получить маппер и сервис записи после возможного восстановления из очереди.
     * 2) Для каждой строки собрать DTO двигателя и вызвать сервис сохранения.
     * 3) Передать ошибки валидации строки в обработчик ошибок Laravel Excel.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $engineRow = $rowMapper->map($rowValues);

                $service->upsertFromRow($engineRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Двигатель', $e->errors(), $rowValues));
            }
        }
    }

    /**
     * Записать ошибки командного импорта двигателей в лог ошибок.
     *
     * Шаги:
     * 1) Пройти по всем ошибкам, которые вернул Laravel Excel или код импорта.
     * 2) Записать номер строки, атрибут, ошибки и исходные значения.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Engine import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Вернуть номер первой строки данных командного импорта двигателей.
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
     * Зарегистрировать событие завершения командного импорта двигателей.
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
     * Опубликовать доменное событие завершения командного импорта двигателей.
     *
     * Шаги:
     * 1) Создать факт EngineCommandImported.
     * 2) Отправить его через события Laravel.
     */
    public static function afterImport(): void
    {
        event(new EngineCommandImported);
    }

    /**
     * Получить сервис сохранения двигателя.
     *
     * Шаги:
     * 1) Вернуть уже переданный сервис, если импорт не проходил через очередь.
     * 2) Иначе резолвить сервис из контейнера во время обработки.
     */
    private function service(): UpsertEngineFromSheetServiceInterface
    {
        return $this->service ??= app(UpsertEngineFromSheetServiceInterface::class);
    }

    /**
     * Получить маппер командной строки двигателя.
     *
     * Шаги:
     * 1) Вернуть уже переданный маппер, если импорт не проходил через очередь.
     * 2) Иначе резолвить маппер из контейнера во время обработки.
     */
    private function rowMapper(): EngineSheetRowMapper
    {
        return $this->rowMapper ??= app(EngineSheetRowMapper::class);
    }
}
