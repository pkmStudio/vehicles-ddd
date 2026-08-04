<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperAdapterExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperDataExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperLengthExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperNomenclatureDetailsDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Exceptions\InvalidWiperKitDataException;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

final readonly class WiperDataExtractor implements WiperDataExtractorInterface
{
    public function __construct(
        private WiperLengthExtractorInterface $lengthExtractor,
        private WiperAdapterExtractorInterface $adapterExtractor,
    ) {}

    public function extractPosition(KitData $kit): WiperKitPositionEnum
    {
        $positions = [];

        foreach ($kit->nomenclatures as $nomenclature) {
            if ($nomenclature->template !== NomenclatureDetailTemplateEnum::WIPER) {
                continue;
            }

            $position = WiperNomenclatureDetailsDTO::fromArray($nomenclature->details)->position();
            if ($position !== null) {
                $positions[$position->value] = $position;
            }
        }

        if ($positions === []) {
            throw InvalidWiperKitDataException::missingWiperPosition($kit->id);
        }

        if (isset($positions[WiperKitPositionEnum::BACK->value])) {
            return WiperKitPositionEnum::BACK;
        }

        $lengths = $this->extractLength($kit, WiperKitPositionEnum::FRONT);
        $adapters = $this->extractAdapters($kit, WiperKitPositionEnum::FRONT);

        $checkAdapters = $adapters->putAdapters === [] ? $adapters->allAdapters : $adapters->putAdapters;
        $hasHook = in_array('H', $checkAdapters, true) || in_array('H83', $checkAdapters, true);
        $isUniversal = $lengths->lengthMain === $lengths->lengthSecond && $hasHook;
        $isSingleHook = $lengths->countWipers === 1 && $hasHook;

        return ($isUniversal || $isSingleHook) ? WiperKitPositionEnum::UNIVERSAL : WiperKitPositionEnum::FRONT;
    }

    public function extractLength(KitData $kit, ?WiperKitPositionEnum $position = null): WiperLengthDTO
    {
        return $this->lengthExtractor->extract(
            kit: $kit,
            position: $position ?? $this->extractPosition($kit),
        );
    }

    public function extractAdapters(KitData $kit, ?WiperKitPositionEnum $position = null): WiperAdaptersDTO
    {
        return $this->adapterExtractor->extract(
            kit: $kit,
            position: $position ?? $this->extractPosition($kit),
        );
    }
}
