<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

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
     * @throws LockTimeoutException
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $engId = $row[0] ?? null;

            if (! $engId) {
                continue;
            }

            $rowValues = $row->toArray();
            $specValues = array_slice($rowValues, self::SPEC_START_COLUMN);
            $isFilledSpecValue = fn ($value) => $value !== null && $value !== '';
            $filledSpecValues = array_filter($specValues, $isFilledSpecValue);

            if (empty($filledSpecValues)) {
                continue;
            }

            try {
                DB::transaction(function () use ($rowValues, $engId, $indexRow): void {
                    $details = $this->templates->buildVehicleDetails(
                        template: DetailTemplateEnum::SPARK_PLUGS,
                        row: $rowValues,
                        startIndex: self::SPEC_START_COLUMN,
                    );

                    $spec = $this->service->upsertByEngine((int) $engId, $details);

                    if (! $spec) {
                        $this->onFailure(new Failure(
                            row: $indexRow + $this->startRow(),
                            attribute: 'eng_id',
                            errors: ["Двигатель с eng_id {$engId} не найден."],
                            values: $rowValues,
                        ));
                    }
                });
            } catch (DetailsDataBuildException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Свечи зажигания',
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
}
