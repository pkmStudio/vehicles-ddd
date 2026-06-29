<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services\Vehicle;

use App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;
use App\Vehicles\Domain\Contracts\Application\Common\Services\WiperSpecificationServiceInterface;
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
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации (front/back)
     */
    public function execute(
        int $vehicleId,
        string $templateSlug,
        array $details,
        ?string $featureValueName = null,
        ?string $name = null,
        ?string $text = null,
    ): void {
        $template = DetailTemplateEnum::from($templateSlug);
        $featureValueId = ! empty($featureValueName)
            ? $this->featureValues->firstByName($featureValueName)?->id
            : null;
        $parts = $this->wiper->splitDetails($details);
        $sideCounts = array_count_values(array_column($parts, 'side'));

        foreach ($parts as $part) {
            $side = (string) $part['side'];
            $partDetails = (array) $part['details'];
            $sideDetails = $this->wiper->sideData($partDetails, $side);
            if (! $this->hasUsableSideDetails($sideDetails)) {
                Log::warning('Импорт дворников: пустые данные стороны пропущены', [
                    'vehicle_id' => $vehicleId,
                    'template' => $template->value,
                    'side' => $side,
                ]);

                continue;
            }

            $data = new PartSpecificationData(
                partableType: Vehicle::class,
                partableId: $vehicleId,
                template: $template,
                details: $partDetails,
                featureValueId: $featureValueId,
                name: $name,
                text: $text,
            );

            $existing = $this->resolveExistingSpecification(
                vehicleId: $vehicleId,
                template: $template,
                side: $side,
                details: $partDetails,
                expectedSideVariants: (int) ($sideCounts[$side] ?? 1),
            );

            if ($existing !== null) {
                $this->warnFeatureValueConflict($existing, $featureValueId, $vehicleId, $side);
                $this->command->update($existing, $data);

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
    ): ?PartSpecification {
        $exact = $this->specifications->firstByVehicleTemplateSideAndDetails($vehicleId, $template, $side, $details);
        if ($exact !== null) {
            return $exact;
        }

        if ($expectedSideVariants > 1) {
            return null;
        }

        $candidates = $this->specifications
            ->forVehicleTemplateAndSide($vehicleId, $template, $side)
            ->filter(fn (PartSpecification $candidate): bool => $this->wiper->detectSideByPartSpecification($candidate) === $side)
            ->values();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->count() > 1) {
            Log::warning('Импорт дворников: несколько PartSpecification для одного авто/шаблона/стороны', [
                'vehicle_id' => $vehicleId,
                'template' => $template->value,
                'side' => $side,
                'part_specification_ids' => $candidates->pluck('id')->all(),
            ]);
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
        PartSpecification $specification,
        ?int $featureValueId,
        int $vehicleId,
        string $side,
    ): void {
        if ($specification->feature_value_id === $featureValueId) {
            return;
        }

        if ($specification->feature_value_id === null && $featureValueId === null) {
            return;
        }

        Log::warning('Конфликт feature_value_id при импорте дворников по стороне', [
            'vehicle_id' => $vehicleId,
            'part_specification_id' => $specification->id,
            'current_feature_value_id' => $specification->feature_value_id,
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
            if ($this->hasUsableValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет одно значение side-details на заполненность.
     *
     * Шаги:
     * 1. Для массивов проверяет вложенные элементы.
     * 2. Считает null и пустую строку пустыми.
     * 3. Все остальные значения считает полезными.
     */
    private function hasUsableValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $innerValue) {
                if ($this->hasUsableValue($innerValue)) {
                    return true;
                }
            }

            return false;
        }

        return $value !== null && $value !== '';
    }
}
