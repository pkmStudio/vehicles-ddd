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
    /**
     * Получает порты расчета и синхронизации применяемости комплектов.
     *
     * Шаги:
     * 1. Сохраняет Warehouse client для чтения активных комплектов.
     * 2. Сохраняет calculator, который выбирает алгоритм по template комплекта.
     * 3. Сохраняет command синхронизации рассчитанных целей применяемости.
     */
    public function __construct(
        private WarehouseKitClientInterface $kits,
        private KitApplicabilityCalculatorInterface $calculator,
        private KitApplicabilityCommandInterface $command,
    ) {}

    /**
     * Пересчитывает применяемость активных комплектов и публикует итоговый факт расчета.
     *
     * Шаги:
     * 1. Создает operation id, если caller не передал внешний id.
     * 2. Итерирует активные Warehouse kits с optional фильтром по kit id и chunk size.
     * 3. Для каждого kit запускает calculator и учитывает skipped result для неподдержанных templates.
     * 4. Синхронизирует рассчитанные targets через command и копит affected kit ids.
     * 5. Перехватывает ошибки отдельных kit-ов, чтобы остальные комплекты продолжили расчет.
     * 6. Собирает aggregate result DTO.
     * 7. Публикует `KitApplicabilityRecalculated`, если caller не отключил событие для chunk-flow.
     */
    public function execute(
        ?int $kitId = null,
        int $chunk = 1000,
        ?string $operationId = null,
        bool $dispatchResultEvent = true,
    ): KitApplicabilityCalculationResultDTO {
        $operationId ??= (string) Str::uuid();
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
            operationId: $operationId,
            processedKits: $processed,
            calculatedKits: $calculated,
            skippedKits: $skipped,
            failedKits: $failed,
            affectedKitIds: array_values(array_unique($affectedKitIds)),
            errors: $errors,
        );

        if ($dispatchResultEvent) {
            event(new KitApplicabilityRecalculated($operationId, $result));
        }

        return $result;
    }
}
