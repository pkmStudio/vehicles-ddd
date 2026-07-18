<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets;

use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Excel-адаптер листа «свечи зажигания» (механика): пропускает пустые строки, собирает details
 * из строки по шаблону и на каждую строку зовёт сценарий записи спецификации.
 */
final class EngineSparkPlugsSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private const int SPEC_START_COLUMN = 9;

    public function __construct(
        string $cacheKey,
        string $lockKey,
        private readonly UpsertEngineSparkPlugSpecServiceInterface $service,
        private readonly TemplatesClientInterface $templates,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * @throws Throwable
     * @throws LockTimeoutException
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $engId = $row[0] ?? null;

            if (! $engId) {
                continue;
            }

            $specValues = array_slice($row->toArray(), self::SPEC_START_COLUMN);
            if (empty(array_filter($specValues, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            DB::beginTransaction();
            try {
                $details = $this->templates->buildVehicleDetails(
                    template: DetailTemplateEnum::SPARK_PLUGS,
                    row: $row->toArray(),
                    startIndex: self::SPEC_START_COLUMN,
                );

                $spec = $this->service->upsertByEngine((int) $engId, $details);

                if (! $spec) {
                    Log::warning('EngineSparkPlugsSheetImport: двигатель не найден', [
                        'row' => $indexRow + $this->startRow(),
                        'eng_id' => $engId,
                    ]);
                    DB::rollBack();

                    continue;
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Свечи зажигания',
                        errors: [$e->getMessage()],
                        values: $row->toArray(),
                    )
                );
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
