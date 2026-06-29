<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine\Sheets;

use App\Vehicles\Domain\Contracts\Application\Import\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Traits\CachesImportFailures;
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
        private readonly int $userId,
        string $cacheKey,
        private readonly UpsertEngineSparkPlugSpecServiceInterface $service,
        private readonly TemplateDataBuilderInterface $templateDataBuilder,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "engine_import_failures_lock_{$userId}";
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
                $startIndex = self::SPEC_START_COLUMN;
                $details = $this->templateDataBuilder->buildByTemplate($row->toArray(), $startIndex, DetailTemplateEnum::SPARK_PLUGS);

                $spec = $this->useCase->execute((int) $engId, $details);

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
