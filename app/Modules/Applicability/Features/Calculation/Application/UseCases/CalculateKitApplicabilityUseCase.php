<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\UseCases;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\KitApplicabilityCalculatorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Illuminate\Support\Str;
use Throwable;

final readonly class CalculateKitApplicabilityUseCase implements CalculateKitApplicabilityUseCaseInterface
{
    public function __construct(
        private WarehouseKitClientInterface $kits,
        private KitApplicabilityCalculatorInterface $calculator,
        private KitApplicabilityCommandInterface $command,
    ) {}

    public function execute(?int $kitId = null, int $chunk = 1000, ?string $runId = null): KitApplicabilityCalculationResultDTO
    {
        $runId ??= (string) Str::uuid();
        $processed = 0;
        $calculated = 0;
        $skipped = 0;
        $failed = 0;
        $affectedKitIds = [];
        $errors = [];

        foreach ($this->kits->activeKits($kitId, $chunk) as $kit) {
            $processed++;

            try {
                $result = $this->calculator->calculate($kit);
                if ($result === null) {
                    $skipped++;
                    continue;
                }

                $this->command->syncCalculatedTargets(
                    kitId: $result->kitId,
                    targetType: $result->targetType,
                    algorithm: $result->algorithm,
                    targetIds: $result->targetIds,
                );

                $calculated++;
                $affectedKitIds[] = $result->kitId;
            } catch (Throwable $exception) {
                $failed++;
                $errors[] = "Kit {$kit->id}: {$exception->getMessage()}";
            }
        }

        $result = new KitApplicabilityCalculationResultDTO(
            runId: $runId,
            processedKits: $processed,
            calculatedKits: $calculated,
            skippedKits: $skipped,
            failedKits: $failed,
            affectedKitIds: array_values(array_unique($affectedKitIds)),
            errors: $errors,
        );

        event(new KitApplicabilityRecalculated($runId, $result));

        return $result;
    }
}
