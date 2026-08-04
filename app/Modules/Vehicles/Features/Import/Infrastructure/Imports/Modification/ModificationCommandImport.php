<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Modification\ModificationCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\Mappers\ModificationCommandRowMapper;
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
 * Excel-адаптер импорта модификаций (механика): читает файл по чанкам и на каждую строку
 * зовёт построчный сценарий. Бизнес-логика строки — в UpsertModificationFromRowService.
 */
final class ModificationCommandImport implements ModificationCommandImportInterface, ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    private ?UpsertModificationFromRowServiceInterface $service = null;

    private ?ModificationCommandRowMapper $rowMapper = null;

    public function __construct(
        UpsertModificationFromRowServiceInterface $service,
        ModificationCommandRowMapper $rowMapper,
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
            $line = $index + $this->startRow();
            $rowValues = $row->toArray();
            try {
                $modificationRow = $rowMapper->map($rowValues);
                $modification = $service->upsertFromRow($modificationRow);

                if (! $modification) {
                    $this->fail($line, "ТС ms_id={$modificationRow->msId} не найдено", $rowValues);
                }
            } catch (ImportRowValidationException $e) {
                $this->fail($line, $e->errors(), $rowValues);
            }
        }
    }

    private function fail(int $row, string|array $errors, array $values): void
    {
        $this->onFailure(new Failure($row, 'Модификация', (array) $errors, $values));
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Modification import failure', [
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
        event(new ModificationCommandImported);
    }

    private function service(): UpsertModificationFromRowServiceInterface
    {
        return $this->service ??= app(UpsertModificationFromRowServiceInterface::class);
    }

    private function rowMapper(): ModificationCommandRowMapper
    {
        return $this->rowMapper ??= app(ModificationCommandRowMapper::class);
    }
}
