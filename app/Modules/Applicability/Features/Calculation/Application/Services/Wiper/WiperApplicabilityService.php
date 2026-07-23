<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperDataExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperVehicleFinderInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

/**
 * Собирает результат расчета применяемости набора дворников.
 */
final readonly class WiperApplicabilityService implements WiperApplicabilityServiceInterface
{
    public function __construct(
        private WiperDataExtractorInterface $extractor,
        private WiperVehicleFinderInterface $finder,
    ) {}

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
