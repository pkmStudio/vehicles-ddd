<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperLengthExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperNomenclatureDetailsDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use RuntimeException;

final readonly class WiperLengthExtractor implements WiperLengthExtractorInterface
{
    public function extract(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO
    {
        return match ($kit->quantityInPackage) {
            1 => $this->extractForOneWiper($kit, $position),
            3 => $this->extractForThreeWipers($kit),
            default => $this->extractForTwoWipers($kit, $position),
        };
    }

    private function extractForTwoWipers(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO
    {
        $first = $this->wiperBySort($kit, 0);
        $isDoubleSku = $first->quantityInPak === 2;
        $firstDetails = $this->wiperDetails($first);

        if ($isDoubleSku) {
            $lengthMain = $firstDetails->lengthMain();
            $lengthSecond = $firstDetails->lengthSecond();
        } else {
            $second = $this->wiperBySort($kit, 1);
            $secondDetails = $this->wiperDetails($second);
            $lengthMain = $this->lengthForPosition($firstDetails, $position);
            $lengthSecond = $this->lengthForPosition($secondDetails, $position);
        }

        return new WiperLengthDTO(
            lengthMain: (int) $lengthMain,
            lengthSecond: (int) $lengthSecond,
            countWipers: 2,
        );
    }

    private function extractForOneWiper(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO
    {
        $wiper = $this->wiperBySort($kit, 0);
        $details = $this->wiperDetails($wiper);

        return new WiperLengthDTO(
            lengthMain: (int) $this->lengthForPosition($details, $position),
            lengthSecond: null,
            countWipers: 1,
        );
    }

    private function extractForThreeWipers(KitData $kit): WiperLengthDTO
    {
        $main = $this->wiperBySort($kit, 0);
        $second = $this->wiperBySort($kit, 2);

        return new WiperLengthDTO(
            lengthMain: (int) $this->wiperDetails($main)->lengthMain(),
            lengthSecond: (int) $this->wiperDetails($second)->lengthMain(),
            countWipers: 3,
        );
    }

    private function wiperBySort(KitData $kit, int $sort): NomenclatureData
    {
        foreach ($kit->nomenclatures as $nomenclature) {
            if ($nomenclature->template === NomenclatureDetailTemplateEnum::WIPER && $nomenclature->sort === $sort) {
                return $nomenclature;
            }
        }

        throw new RuntimeException("Wiper nomenclature with sort {$sort} not found for kit {$kit->id}");
    }

    private function wiperDetails(NomenclatureData $nomenclature): WiperNomenclatureDetailsDTO
    {
        return WiperNomenclatureDetailsDTO::fromArray($nomenclature->details);
    }

    private function lengthForPosition(WiperNomenclatureDetailsDTO $details, WiperKitPositionEnum $position): ?int
    {
        return $position === WiperKitPositionEnum::BACK ? $details->lengthRear() : $details->lengthMain();
    }
}
