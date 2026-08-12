<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperDataExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperVehicleFinderInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;

/**
 * Собирает результат расчета применяемости набора дворников.
 */
final readonly class WiperApplicabilityService implements WiperApplicabilityServiceInterface
{
    /**
     * Получает collaborators алгоритма применяемости дворников.
     *
     * Шаги:
     * 1. Сохраняет extractor параметров комплекта: position, lengths и adapters.
     * 2. Сохраняет finder vehicle part specifications по расчетным параметрам.
     */
    public function __construct(
        private WiperDataExtractorInterface $extractor,
        private WiperVehicleFinderInterface $finder,
    ) {}

    /**
     * Рассчитывает применяемость комплекта дворников к vehicle part specifications.
     *
     * Шаги:
     * 1. Определяет позицию комплекта дворников.
     * 2. Извлекает длины и adapters для этой позиции.
     * 3. Находит совместимые vehicle part specifications.
     * 4. Преобразует найденные specification ids в integer targets.
     * 5. Возвращает result DTO для синхронизации calculated PART_SPECIFICATION targets.
     */
    public function calculate(KitData $kit): KitApplicabilityKitResultDTO
    {
        $position = $this->extractor->extractPosition($kit);
        $wipers = $this->extractor->extractLength($kit, $position);
        $adapters = $this->extractor->extractAdapters($kit, $position);
        $specifications = $this->finder->find($wipers, $adapters, $position);

        $targetIds = $specifications
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return new KitApplicabilityKitResultDTO(
            kitId: $kit->id,
            algorithm: KitApplicabilityAlgorithmEnum::WIPER,
            targetType: ApplicabilityTargetTypeEnum::PART_SPECIFICATION,
            targetIds: $targetIds,
        );
    }
}
