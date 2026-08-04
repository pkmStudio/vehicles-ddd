<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers\EngineModificationCommandRowMapper;
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
 * Excel-адаптер импорта связи двигатель↔модификация (механика): читает файл по чанкам и на
 * каждую строку зовёт сценарий привязки. Логика строки — в LinkEngineModificationFromRowService.
 */
final class EngineModificationImport implements EngineModificationImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    private ?LinkEngineModificationFromRowServiceInterface $service = null;

    private ?EngineModificationCommandRowMapper $rowMapper = null;

    public function __construct(
        LinkEngineModificationFromRowServiceInterface $service,
        EngineModificationCommandRowMapper $rowMapper,
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
        return 500;
    }

    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $index => $row) {
            $rowValues = $row->toArray();
            try {
                $engineModificationRow = $rowMapper->map($rowValues);

                $service->linkFromRow($engineModificationRow);
            } catch (ImportRowValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Связь двигатель-модификация', $e->errors(), $rowValues));
            }
        }
    }

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

    public function startRow(): int
    {
        return 2;
    }

    private function service(): LinkEngineModificationFromRowServiceInterface
    {
        return $this->service ??= app(LinkEngineModificationFromRowServiceInterface::class);
    }

    private function rowMapper(): EngineModificationCommandRowMapper
    {
        return $this->rowMapper ??= app(EngineModificationCommandRowMapper::class);
    }
}
