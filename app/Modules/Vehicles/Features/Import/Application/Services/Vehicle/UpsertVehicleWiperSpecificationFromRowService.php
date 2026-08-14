<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleWiperSpecificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\PartSpecificationEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationUpdated;
use Psr\Log\LoggerInterface;

/**
 * Сервис импорта спецификаций «дворники» для ТС.
 * Дворники хранятся по ОДНОЙ записи на сторону (front/back): собранные из строки details
 * разбиваются по сторонам (доменный сервис), и каждая сторона upsert-ится отдельно —
 * существующая запись стороны ищется по `template + side + details`, затем update либо create.
 */
final readonly class UpsertVehicleWiperSpecificationFromRowService implements UpsertVehicleWiperSpecificationFromRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-part-specification-import';

    /**
     * Инициализирует порты сценария импорта спецификаций дворников.
     *
     * Шаги:
     * 1) Сохранить repositories для feature value, part specification и vehicle lookup.
     * 2) Сохранить command записи part specifications.
     * 3) Сохранить Templates client для split/side helpers.
     * 4) Сохранить logger для actionable import anomalies.
     */
    public function __construct(
        private FeatureValueRepositoryInterface $featureValues,
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationCommandInterface $command,
        private TemplatesClientInterface $templates,
        private VehicleRepositoryInterface $vehicles,
        private LoggerInterface $logger,
    ) {}

    /**
     * Импортирует строку спецификаций дворников для автомобиля.
     *
     * Шаги:
     * 1) Проверить наличие `ms_id` и существование автомобиля.
     * 2) Если template slug пустой — пропустить строку.
     * 3) Разрешить template enum и optional feature value.
     * 4) Разбить details дворников на side-specific части через Templates.
     * 5) Пропустить пустые стороны с warning-логом.
     * 6) Найти существующую specification для стороны или создать новую.
     * 7) Выполнить update/create через command и опубликовать catalog mutation event.
     */
    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void
    {
        if ($row->msId === null) {
            throw ImportRowValidationException::fromMessage('Не указан ms_id для записи спецификации дворников.');
        }

        $vehicle = $this->vehicles->findByMsId($row->msId);
        if ($vehicle?->id === null) {
            throw ImportRowReferenceNotFoundException::withMessage("ТС с ms_id {$row->msId} не найдено. Сначала импортируйте основной лист.");
        }

        if ($row->templateSlug === null) {
            return;
        }

        $template = DetailTemplateEnum::from($row->templateSlug);
        $featureValueId = null;
        if (! empty($row->featureValueName)) {
            $featureValue = $this->featureValues->findByName($row->featureValueName);
            if ($featureValue === null) {
                throw ImportRowReferenceNotFoundException::withMessage("Особенность \"{$row->featureValueName}\" не найдена. Сначала импортируйте особенности.");
            }

            $featureValueId = $featureValue->id;
        }

        $parts = $this->templates->splitVehicleWiperDetails($row->details);
        $sideCounts = array_count_values(array_column($parts, 'side'));

        foreach ($parts as $part) {
            $side = (string) $part['side'];
            $partDetails = (array) $part['details'];
            $sideDetails = $this->templates->vehicleWiperSideData($partDetails, $side);
            $hasUsableSideDetails = $this->hasUsableSideDetails($sideDetails);

            if (! $hasUsableSideDetails) {
                $this->logger->warning('Импорт дворников: пустые данные стороны пропущены', [
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

                $updatedData = new PartSpecificationData(
                    partableType: $data->partableType,
                    partableId: $vehicle->id,
                    template: $data->template,
                    details: $data->details,
                    featureValueId: $data->featureValueId,
                    name: $data->name,
                    text: $data->text,
                    id: $existing->id,
                );
                $specification = $this->command->update($updatedData);
                $payload = new PartSpecificationEventPayloadDTO(
                    id: (int) $specification->id,
                    partableType: $specification->partableType instanceof PartableTypeEnum
                        ? $specification->partableType
                        : PartableTypeEnum::from((string) $specification->partableType),
                    partableId: $specification->partableId,
                    template: $specification->template,
                    details: $specification->details,
                    featureValueId: $specification->featureValueId,
                    name: $specification->name,
                    text: $specification->text,
                );

                event(new PartSpecificationUpdated(
                    self::IMPORT_USER_ID,
                    self::OPERATION_ID,
                    $payload,
                ));

                continue;
            }

            $specification = $this->command->create($data);
            $payload = new PartSpecificationEventPayloadDTO(
                id: (int) $specification->id,
                partableType: $specification->partableType instanceof PartableTypeEnum
                    ? $specification->partableType
                    : PartableTypeEnum::from((string) $specification->partableType),
                partableId: $specification->partableId,
                template: $specification->template,
                details: $specification->details,
                featureValueId: $specification->featureValueId,
                name: $specification->name,
                text: $specification->text,
            );

            event(new PartSpecificationCreated(
                self::IMPORT_USER_ID,
                self::OPERATION_ID,
                $payload,
            ));
        }
    }

    /**
     * Находит существующую целевую спецификацию для записи.
     *
     * Шаги:
     * 1) Сначала ищет точное совпадение по JSON details.
     * 2) Если в текущем импорте у стороны один вариант, допускает update единственного side-кандидата.
     * 3) Если вариантов несколько или кандидатов несколько, не выбирает неоднозначную запись.
     */
    private function resolveExistingSpecification(
        int $vehicleId,
        DetailTemplateEnum $template,
        string $side,
        array $details,
        int $expectedSideVariants,
    ): ?PartSpecificationData {
        $exact = $this->specifications->findByVehicleTemplateSideAndDetails($vehicleId, $template, $side, $details);
        if ($exact !== null) {
            return $exact;
        }

        if ($expectedSideVariants > 1) {
            return null;
        }

        $matchesSide = fn (PartSpecificationData $candidate): bool => $this->templates->detectVehicleWiperSide($candidate->details) === $side;

        $candidates = $this->specifications
            ->forVehicleTemplateAndSide($vehicleId, $template, $side)
            ->filter($matchesSide)
            ->values();

        $hasSingleCandidate = $candidates->count() === 1;

        if ($hasSingleCandidate) {
            return $candidates->first();
        }

        return null;
    }

    /**
     * Логирует конфликт feature value перед обновлением существующей записи.
     *
     * Шаги:
     * 1) Сравнивает текущее значение с импортируемым.
     * 2) Игнорирует полное совпадение и пару null/null.
     * 3) Пишет warning с контекстом записи.
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

        $this->logger->warning('Конфликт feature_value_id при импорте дворников по стороне', [
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
     * 1) Рекурсивно проходит по значениям стороны.
     * 2) Игнорирует null, пустые строки и пустые массивы.
     * 3) Возвращает true при первом значимом значении.
     */
    private function hasUsableSideDetails(array $sideDetails): bool
    {
        foreach ($sideDetails as $value) {
            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }

                $hasUsableSideDetails = $this->hasUsableSideDetails($value);

                if ($hasUsableSideDetails) {
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
