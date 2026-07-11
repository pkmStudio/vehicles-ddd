<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Vehicle;

use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Сервис импорта спецификаций «дворники» для ТС.
 * Дворники хранятся по ОДНОЙ записи на сторону (front/back): собранные из строки details
 * разбиваются по сторонам (доменный сервис), и каждая сторона upsert-ится отдельно —
 * существующая запись стороны ищется по `template + side + details`, затем update либо create.
 */
final readonly class VehicleWiperSpecificationImportService implements VehicleWiperSpecificationImportServiceInterface
{
    public function __construct(
        private FeatureValueRepositoryInterface $featureValues,
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationCommandInterface $command,
        private WiperSpecificationServiceInterface $wiper,
        private VehicleRepositoryInterface $vehicles,
    ) {}

    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void
    {
        if ($row->msId === null) {
            throw new RuntimeException('Не указан ms_id для записи спецификации дворников.');
        }

        $vehicle = $this->vehicles->firstByMsId($row->msId);
        if ($vehicle?->id === null) {
            throw new RuntimeException("ТС с ms_id {$row->msId} не найдено. Сначала импортируйте основной лист.");
        }

        if ($row->templateSlug === null) {
            return;
        }

        $template = DetailTemplateEnum::from($row->templateSlug);
        $featureValueId = null;
        if (! empty($row->featureValueName)) {
            $featureValue = $this->featureValues->firstByName($row->featureValueName);
            if ($featureValue === null) {
                throw new RuntimeException("Особенность \"{$row->featureValueName}\" не найдена. Сначала импортируйте особенности.");
            }

            $featureValueId = $featureValue->id;
        }

        $parts = $this->wiper->splitDetails($row->details);
        $sideCounts = array_count_values(array_column($parts, 'side'));

        foreach ($parts as $part) {
            $side = (string) $part['side'];
            $partDetails = (array) $part['details'];
            $sideDetails = $this->wiper->sideData($partDetails, $side);
            if (! $this->hasUsableSideDetails($sideDetails)) {
                Log::warning('Импорт дворников: пустые данные стороны пропущены', [
                    'vehicle_id' => $vehicle->id,
                    'template' => $template->value,
                    'side' => $side,
                ]);

                continue;
            }

            $data = new PartSpecificationData(
                partableType: PartableTypeEnum::VEHICLE->value,
                partableId: $vehicle->id,
                template: $template,
                details: $partDetails,
                featureValueId: $featureValueId,
                name: $row->name,
                text: $row->text,
            );

            $existing = $this->resolveExistingSpecification(
                vehicleId: $vehicle->id,
                template: $template,
                side: $side,
                details: $partDetails,
                expectedSideVariants: (int) ($sideCounts[$side] ?? 1),
            );

            if ($existing !== null) {
                $this->warnFeatureValueConflict($existing, $featureValueId, $vehicle->id, $side);
                $this->command->update(new PartSpecificationData(
                    partableType: $data->partableType,
                    partableId: $vehicle->id,
                    template: $data->template,
                    details: $data->details,
                    featureValueId: $data->featureValueId,
                    name: $data->name,
                    text: $data->text,
                    id: $existing->id,
                ));

                continue;
            }

            $this->command->create($data);
        }
    }

    /**
     * Находит существующую целевую спецификацию для записи.
     *
     * Шаги:
     * 1. Сначала ищет точное совпадение по JSON details.
     * 2. Если в текущем импорте у стороны один вариант, допускает update единственного side-кандидата.
     * 3. Если вариантов несколько или кандидатов несколько, не выбирает неоднозначную запись.
     */
    private function resolveExistingSpecification(
        int $vehicleId,
        DetailTemplateEnum $template,
        string $side,
        array $details,
        int $expectedSideVariants,
    ): ?PartSpecificationData {
        $exact = $this->specifications->firstByVehicleTemplateSideAndDetails($vehicleId, $template, $side, $details);
        if ($exact !== null) {
            return $exact;
        }

        if ($expectedSideVariants > 1) {
            return null;
        }

        $candidates = $this->specifications
            ->forVehicleTemplateAndSide($vehicleId, $template, $side)
            ->filter(fn (PartSpecificationData $candidate): bool => $this->wiper->detectSide($candidate->details) === $side)
            ->values();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        return null;
    }

    /**
     * Логирует конфликт feature value перед обновлением существующей записи.
     *
     * Шаги:
     * 1. Сравнивает текущее значение с импортируемым.
     * 2. Игнорирует полное совпадение и пару null/null.
     * 3. Пишет warning с контекстом записи.
     */
    private function warnFeatureValueConflict(
        PartSpecificationData $specification,
        ?int $featureValueId,
        int $vehicleId,
        string $side,
    ): void {
        if ($specification->featureValueId === $featureValueId) {
            return;
        }

        if ($specification->featureValueId === null && $featureValueId === null) {
            return;
        }

        Log::warning('Конфликт feature_value_id при импорте дворников по стороне', [
            'vehicle_id' => $vehicleId,
            'part_specification_id' => $specification->id,
            'current_feature_value_id' => $specification->featureValueId,
            'import_feature_value_id' => $featureValueId,
            'side' => $side,
        ]);
    }

    /**
     * Проверяет наличие фактических значений в side-details.
     *
     * Шаги:
     * 1. Рекурсивно проходит по значениям стороны.
     * 2. Игнорирует null, пустые строки и пустые массивы.
     * 3. Возвращает true при первом значимом значении.
     */
    private function hasUsableSideDetails(array $sideDetails): bool
    {
        foreach ($sideDetails as $value) {
            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }

                if ($this->hasUsableSideDetails($value)) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

}
