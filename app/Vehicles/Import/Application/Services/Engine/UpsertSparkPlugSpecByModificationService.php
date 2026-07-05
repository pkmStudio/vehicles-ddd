<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Domain\Models\Engine as PartableEngineType;
use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Vehicles\Import\Domain\DTOs\ModificationSparkPlugResult;
use App\Vehicles\Import\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;

/**
 * Use-case: записать спецификацию «свечи зажигания» всем двигателям модификации.
 * Резолвит модификацию по (ms_id + mod_id); для ms_id<0 (синтетическая модель) берёт ms_id
 * родителя. Двигатели без искрового зажигания (дизель/электро) пропускаются — их перечень
 * возвращается в результате для отчёта. details (сборка из строки) приходят готовыми из адаптера.
 */
final readonly class UpsertSparkPlugSpecByModificationService implements UpsertSparkPlugSpecByModificationServiceInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private PartSpecificationCommandInterface $partSpecs,
    ) {}

    /**
     * @param  array<string, mixed>  $details
     */
    public function upsertByModification(int $msId, int $modId, array $details): ModificationSparkPlugResult
    {
        [$resolvedMsId, $reason] = $this->resolveMsId($msId);
        if ($resolvedMsId === null) {
            return ModificationSparkPlugResult::notFound($reason);
        }

        $modification = $this->modifications->firstByMsIdAndModIdWithEngines($resolvedMsId, $modId);
        if (! $modification) {
            return ModificationSparkPlugResult::notFound("Модификация (ms_id: {$resolvedMsId}, mod_id: {$modId}) не найдена.");
        }

        $written = 0;
        $skipped = [];

        foreach ($modification->engines ?? [] as $engine) {
            if ($engine->engFuelType === null || ! $engine->engFuelType->needsSparkPlugs()) {
                $skipped[] = ['code' => $engine->codeEngine, 'fuel' => $engine->engFuelType?->value];

                continue;
            }

            $this->partSpecs->upsert(new PartSpecificationData(
                partableType: PartableEngineType::class,
                partableId: (int) $engine->id,
                template: DetailTemplateEnum::SPARK_PLUGS,
                details: $details,
            ));
            $written++;
        }

        return ModificationSparkPlugResult::written($written, $skipped);
    }

    /**
     * @return array{0: ?int, 1: ?string} [резолвнутый ms_id | null, причина-если-null]
     */
    private function resolveMsId(int $msId): array
    {
        if ($msId >= 0) {
            return [$msId, null];
        }

        $vehicle = $this->vehicles->firstByMsId($msId);
        if (! $vehicle) {
            return [null, "Модель (ms_id: {$msId}) не найдена."];
        }

        $parentMsId = $this->vehicles->parentMsId($msId);
        if (! $parentMsId) {
            return [null, "Модель (ms_id: {$msId}) должна иметь родителя."];
        }

        return [$parentMsId, null];
    }
}
