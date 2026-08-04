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
 * Excel-адаптер импорта двигателей (механика): читает файл по чанкам и на каждую строку
 * зовёт построчный сценарий. Бизнес-логика строки — в UpsertEngineFromSheetService.
 */
final class EngineCommandImport implements EngineCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    private ?UpsertEngineFromSheetServiceInterface $service = null;

    private ?EngineSheetRowMapper $rowMapper = null;

    public function __construct(
        UpsertEngineFromSheetServiceInterface $service,
        EngineSheetRowMapper $rowMapper,
    ) {
        $this->service = $service;
        $this->rowMapper = $rowMapper;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->service = null;
        $this->rowMapper = null;
    }

    public function import(string $path): void
    {
        Excel::import($this, $path);
    }

    public function chunkSize(): int
    {
        return 100;
    }

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

    public function startRow(): int
    {
        return 2;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport'],
        ];
    }

    public static function afterImport(): void
    {
        event(new EngineCommandImported);
    }

    private function service(): UpsertEngineFromSheetServiceInterface
    {
        return $this->service ??= app(UpsertEngineFromSheetServiceInterface::class);
    }

    private function rowMapper(): EngineSheetRowMapper
    {
        return $this->rowMapper ??= app(EngineSheetRowMapper::class);
    }
}
