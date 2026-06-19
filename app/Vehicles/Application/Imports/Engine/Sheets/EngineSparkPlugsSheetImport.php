<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Imports\Engine\Sheets;

use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Templates\Engine\EngineTemplateFactory;
use App\Vehicles\Infrastructure\Support\DetailsBuilder;
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

final class EngineSparkPlugsSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    private const string TEMPLATE_KEY = 'sparkPlugs';

    private const int SPEC_START_COLUMN = 9;

    private ?array $templateConfig = null;

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly PartSpecificationCommandInterface $partSpecs,
        private readonly EngineRepositoryInterface $engines,
        private readonly DetailsBuilder $detailsBuilder,
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
                $engine = $this->engines->firstByEngId((int) $engId);

                if (! $engine) {
                    Log::warning('EngineSparkPlugsSheetImport: двигатель не найден', [
                        'row' => $indexRow + $this->startRow(),
                        'eng_id' => $engId,
                    ]);
                    DB::rollBack();

                    continue;
                }

                $details = $this->detailsBuilder->buildDetails($row->toArray(), self::SPEC_START_COLUMN, $this->resolveTemplate());

                $this->partSpecs->upsert(new PartSpecificationData(
                    partableType: Engine::class,
                    partableId: $engine->id,
                    template: DetailTemplateEnum::SPARK_PLUGS,
                    details: $details,
                ));

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

    private function resolveTemplate(): array
    {
        if ($this->templateConfig === null) {
            $this->templateConfig = EngineTemplateFactory::make(self::TEMPLATE_KEY)->getArrayTemplate();
        }

        return $this->templateConfig;
    }
}
