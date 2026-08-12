<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\ModificationSparkPlugResultDTO;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationUpdated;

/**
 * Use-case: записать спецификацию «свечи зажигания» всем двигателям модификации.
 * Резолвит модификацию по (ms_id + mod_id); для ms_id<0 (синтетическая модель) берёт ms_id
 * родителя. Двигатели без искрового зажигания (дизель/электро) пропускаются — их перечень
 * возвращается в результате для отчёта. details (сборка из строки) приходят готовыми из адаптера.
 */
final readonly class UpsertSparkPlugSpecByModificationService implements UpsertSparkPlugSpecByModificationServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-part-specification-import';

    /**
     * Инициализирует порты сценария записи свечей по модификации.
     *
     * Шаги:
     * 1) Сохранить repositories для vehicle/modification/specification lookup.
     * 2) Сохранить command записи part specifications.
     * 3) Сохранить factory сборки `PartSpecificationData`.
     */
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private PartSpecificationCommandInterface $partSpecs,
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationDataFactoryInterface $factory,
    ) {}

    /**
     * Записывает спецификацию свечей всем подходящим двигателям модификации.
     *
     * Шаги:
     * 1) Разрешить исходный `ms_id`, включая синтетические модели через parent.
     * 2) Найти модификацию с привязанными двигателями.
     * 3) Пройти по двигателям и пропустить те, где fuel type отсутствует или не требует свечей.
     * 4) Для подходящего двигателя собрать specification data.
     * 5) Найти существующую specification по owner/template/feature value.
     * 6) Выполнить create или update через command и опубликовать event.
     * 7) Вернуть result с количеством записей и списком пропущенных двигателей.
     *
     * @param  array<string, mixed>  $details
     */
    public function upsertByModification(int $msId, int $modId, array $details): ModificationSparkPlugResultDTO
    {
        [$resolvedMsId, $reason] = $this->resolveMsId($msId);
        if ($resolvedMsId === null) {
            return new ModificationSparkPlugResultDTO(found: false, notFoundReason: $reason);
        }

        $modification = $this->modifications->findByMsIdAndModIdWithEngines($resolvedMsId, $modId);
        if (! $modification) {
            return new ModificationSparkPlugResultDTO(
                found: false,
                notFoundReason: "Модификация (ms_id: {$resolvedMsId}, mod_id: {$modId}) не найдена.",
            );
        }

        $written = 0;
        $skipped = [];

        foreach ($modification->engines ?? [] as $engine) {
            $fuelTypeMissing = $engine->fuelType === null;
            $needsSparkPlugs = $engine->fuelType?->needsSparkPlugs() ?? false;

            if ($fuelTypeMissing || ! $needsSparkPlugs) {
                $skipped[] = ['code' => $engine->codeEngine, 'fuel' => $engine->fuelType?->value];

                continue;
            }

            $specification = $this->factory->make((int) $engine->id, $details);
            $existing = $this->specifications->findByPartableTemplateAndFeatureValue(
                partableType: $specification->partableType,
                partableId: $specification->partableId,
                template: $specification->template,
                featureValueId: $specification->featureValueId,
            );
            $specification = $existing === null
                ? $this->partSpecs->create($specification)
                : $this->partSpecs->update($this->factory->make((int) $engine->id, $details, $existing->id));

            event($existing === null
                ? new PartSpecificationCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $specification->toArray())
                : new PartSpecificationUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $specification->toArray()));

            $written++;
        }

        return new ModificationSparkPlugResultDTO(found: true, writtenCount: $written, skippedEngines: $skipped);
    }

    /**
     * Разрешает vehicle `ms_id` для поиска модификации.
     *
     * Шаги:
     * 1) Если `ms_id` не синтетический — вернуть его без изменений.
     * 2) Для отрицательного `ms_id` найти vehicle snapshot.
     * 3) Если модель не найдена — вернуть null и причину.
     * 4) Если у модели нет parent `ms_id` — вернуть null и причину.
     * 5) Вернуть parent `ms_id` как id для поиска модификации.
     *
     * @return array{0: ?int, 1: ?string} [резолвнутый ms_id | null, причина-если-null]
     */
    private function resolveMsId(int $msId): array
    {
        if ($msId >= 0) {
            return [$msId, null];
        }

        $vehicle = $this->vehicles->findByMsId($msId);
        if (! $vehicle) {
            return [null, "Модель (ms_id: {$msId}) не найдена."];
        }

        $parentMsId = $vehicle->parentMsId;
        if (! $parentMsId) {
            return [null, "Модель (ms_id: {$msId}) должна иметь родителя."];
        }

        return [$parentMsId, null];
    }
}
