<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers\EngineMainSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер основного листа двигателей (механика): сопоставляет колонки Excel
 * с EngineSheetRowDTO и на каждую строку зовёт обычный сценарий upsert двигателя.
 */
final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private ?UpsertEngineFromSheetServiceInterface $service = null;

    private ?EngineMainSheetRowMapper $rowMapper = null;

    public function __construct(
        string $cacheKey,
        string $lockKey,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'cacheKey' => $this->cacheKey,
            'lockKey' => $this->lockKey,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->cacheKey = (string) $data['cacheKey'];
        $this->lockKey = (string) $data['lockKey'];
        $this->service = null;
        $this->rowMapper = null;
    }

    public function collection(Collection $collection): void
    {
        $rowMapper = $this->rowMapper();
        $service = $this->service();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            try {
                DB::transaction(function () use ($rowMapper, $rowValues, $service): void {
                    $engineRow = $rowMapper->map($rowValues);

                    $service->upsertFromRow($engineRow);
                });
            } catch (ImportRowValidationException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Двигатели',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    )
                );
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    private function service(): UpsertEngineFromSheetServiceInterface
    {
        return $this->service ??= app(UpsertEngineFromSheetServiceInterface::class);
    }

    private function rowMapper(): EngineMainSheetRowMapper
    {
        return $this->rowMapper ??= app(EngineMainSheetRowMapper::class);
    }
}
