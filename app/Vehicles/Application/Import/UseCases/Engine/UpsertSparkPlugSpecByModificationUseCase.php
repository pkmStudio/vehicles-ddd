<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpsertSparkPlugSpecByModificationUseCaseInterface;
use App\Vehicles\Domain\DTOs\ModificationSparkPlugResult;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Engine;

/**
 * Use-case: записать спецификацию «свечи зажигания» всем двигателям модификации.
 * Резолвит модификацию по (ms_id + mod_id); для ms_id<0 (синтетическая модель) берёт ms_id
 * родителя. Двигатели без искрового зажигания (дизель/электро) пропускаются — их перечень
 * возвращается в результате для отчёта. details (сборка из строки) приходят готовыми из адаптера.
 */
final readonly class UpsertSparkPlugSpecByModificationUseCase implements UpsertSparkPlugSpecByModificationUseCaseInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private PartSpecificationCommandInterface $partSpecs,
    ) {}

    /**
     * @param  array<string, mixed>  $details
     */
    public function execute(int $msId, int $modId, array $details): ModificationSparkPlugResult
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

        foreach ($modification->engines as $engine) {
            if ($engine->eng_fuel_type === null || ! $engine->eng_fuel_type->needsSparkPlugs()) {
                $skipped[] = ['code' => $engine->code_engine, 'fuel' => $engine->eng_fuel_type?->value];

                continue;
            }

            $this->partSpecs->upsert(new PartSpecificationData(
                partableType: Engine::class,
                partableId: $engine->id,
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

        $parentMsId = $vehicle->parent?->ms_id;
        if (! $parentMsId) {
            return [null, "Модель (ms_id: {$msId}) должна иметь родителя."];
        }

        return [(int) $parentMsId, null];
    }
}
