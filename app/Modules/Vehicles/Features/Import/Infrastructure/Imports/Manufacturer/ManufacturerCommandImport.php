<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers\ManufacturerTdRowMapper;
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
 * Excel-адаптер командного импорта производителей: читает файл и передаёт строки сервису записи.
 */
final class ManufacturerCommandImport implements ManufacturerCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    private ?UpsertManufacturerFromTdRowServiceInterface $service = null;

    private ?ManufacturerTdRowMapper $rowMapper = null;

    /**
     * Запустить командный импорт производителей.
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
     * Вернуть размер чанка для командного импорта производителей.
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
     * Обработать пачку строк командного импорта производителей.
     *
     * Шаги:
     * 1) Получить маппер и сервис записи после возможного восстановления из очереди.
     * 2) Для каждой строки собрать DTO производителя и вызвать сервис сохранения.
     * 3) Передать ошибки валидации строки в обработчик ошибок Laravel Excel.
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $manufacturerRow = $rowMapper->map($rowValues);
                $service->upsertFromRow($manufacturerRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure(
                    $index + $this->startRow(),
                    'Производитель',
                    $e->errors(),
                    $rowValues,
                ));
            }
        }
    }

    /**
     * Записать ошибки командного импорта производителей в лог ошибок.
     *
     * Шаги:
     * 1) Пройти по всем ошибкам, которые вернул Laravel Excel или код импорта.
     * 2) Записать номер строки, атрибут, ошибки и исходные значения.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Manufacturer import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Вернуть номер первой строки данных командного импорта производителей.
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
     * Зарегистрировать событие завершения командного импорта производителей.
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
     * Опубликовать доменное событие завершения командного импорта производителей.
     *
     * Шаги:
     * 1) Создать факт ManufacturerCommandImported.
     * 2) Отправить его через события Laravel.
     */
    public static function afterImport(): void
    {
        event(new ManufacturerCommandImported);
    }

    /**
     * Получить сервис сохранения производителя.
     *
     * Шаги:
     * 1) Лениво получить сервис из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function service(): UpsertManufacturerFromTdRowServiceInterface
    {
        return $this->service ??= app(UpsertManufacturerFromTdRowServiceInterface::class);
    }

    /**
     * Получить маппер TecDoc-строки производителя.
     *
     * Шаги:
     * 1) Лениво получить маппер из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function rowMapper(): ManufacturerTdRowMapper
    {
        return $this->rowMapper ??= app(ManufacturerTdRowMapper::class);
    }
}
