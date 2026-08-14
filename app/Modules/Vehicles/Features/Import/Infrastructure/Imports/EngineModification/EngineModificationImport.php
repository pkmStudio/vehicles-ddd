<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers\EngineModificationTdRowMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер импорта связи двигатель-модификация: читает файл и передаёт строки сервису привязки.
 */
final class EngineModificationImport implements EngineModificationImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    private ?LinkEngineModificationFromRowServiceInterface $service = null;

    private ?EngineModificationTdRowMapper $rowMapper = null;

    /**
     * Запустить импорт файла связей двигателя и модификации.
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
     * Вернуть размер чанка чтения строк связей.
     *
     * Шаги:
     * 1) Зафиксировать размер пачки для построчной привязки.
     * 2) Вернуть значение, которое использует Laravel Excel.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Обработать пачку строк импорта связей.
     *
     * Шаги:
     * 1) Получить маппер и сервис привязки после возможного восстановления из очереди.
     * 2) Преобразовать каждую строку Excel в командный DTO связи.
     * 3) Передать DTO в сервис привязки или сохранить ошибку валидации.
     *
     * @param  Collection<int, mixed>  $collection
     */
    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $engineModificationRow = $rowMapper->map($rowValues);
                $service->linkFromRow($engineModificationRow);
            } catch (ImportRowValidationException|ImportRowReferenceNotFoundException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', $e->errors(), $rowValues));
            }
        }
    }

    /**
     * Записать ошибки импорта связей в лог ошибок.
     *
     * Шаги:
     * 1) Пройти по ошибкам, полученным от маппера или Laravel Excel.
     * 2) Записать номер строки, атрибут, ошибки и исходные значения.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('EngineModification import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Вернуть номер первой строки данных импорта связей.
     *
     * Шаги:
     * 1) Пропустить строку заголовков Excel.
     * 2) Начать чтение со второй строки.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Получить сервис привязки двигателя к модификации.
     *
     * Шаги:
     * 1) Лениво получить сервис из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function service(): LinkEngineModificationFromRowServiceInterface
    {
        return $this->service ??= app(LinkEngineModificationFromRowServiceInterface::class);
    }

    /**
     * Получить маппер строки связи двигателя и модификации.
     *
     * Шаги:
     * 1) Лениво получить маппер из контейнера во время обработки.
     * 2) Закешировать resolved instance на время обработки.
     */
    private function rowMapper(): EngineModificationTdRowMapper
    {
        return $this->rowMapper ??= app(EngineModificationTdRowMapper::class);
    }
}
