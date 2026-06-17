<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Engine\Sheets;

use App\Vehicles\Enums\DetailTemplateEnum;
use App\Vehicles\Templates\Engine\EngineTemplateFactory;
use App\Vehicles\Models\Engine;
use App\Vehicles\Traits\BuildDetails;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use RuntimeException;
use Throwable;

final class EngineSparkPlugsSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use BuildDetails;
    use CachesImportFailures;

    private const string TEMPLATE_KEY = 'sparkPlugs';

    private const int SPEC_START_COLUMN = 9;

    private ?array $templateConfig = null;

    public function __construct(private readonly int $userId, string $cacheKey)
    {
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

            try {
                $templateConfig = $this->resolveTemplate();
            } catch (RuntimeException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Свечи зажигания',
                        errors: [$e->getMessage()],
                        values: $row->toArray(),
                    )
                );

                continue;
            }

            DB::beginTransaction();
            try {
                $engine = Engine::query()->where('eng_id', $engId)->first();

                if (! $engine) {
                    Log::warning('EngineSparkPlugsSheetImport: двигатель не найден', [
                        'row' => $indexRow + $this->startRow(),
                        'eng_id' => $engId,
                    ]);
                    DB::rollBack();

                    continue;
                }

                $startDetailIndex = self::SPEC_START_COLUMN;
                $rowArray = $row->toArray();
                $details = $this->buildDetails($rowArray, $startDetailIndex, $templateConfig);

                $engine->partSpecifications()->updateOrCreate(
                    ['template' => DetailTemplateEnum::SPARK_PLUGS],
                    ['details' => $details]
                );

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

    /**
     * @throws LockTimeoutException
     */
    private function resolveTemplate(): array
    {
        if ($this->templateConfig === null) {
            $this->templateConfig = EngineTemplateFactory::make(self::TEMPLATE_KEY)->getArrayTemplate();
        }

        return $this->templateConfig;
    }
}
