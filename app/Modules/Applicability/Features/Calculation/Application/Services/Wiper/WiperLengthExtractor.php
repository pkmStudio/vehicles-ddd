<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperLengthExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
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

        if ($isDoubleSku) {
            $lengthMain = $first->details['length_main'] ?? null;
            $lengthSecond = $first->details['length_second'] ?? null;
        } else {
            $second = $this->wiperBySort($kit, 1);
            $mainField = $position === WiperKitPositionEnum::BACK ? 'length_rear' : 'length_main';
            $secondField = $position === WiperKitPositionEnum::BACK ? 'length_rear' : 'length_main';
            $lengthMain = $first->details[$mainField] ?? null;
            $lengthSecond = $second->details[$secondField] ?? null;
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
        $field = $position === WiperKitPositionEnum::BACK ? 'length_rear' : 'length_main';

        return new WiperLengthDTO(
            lengthMain: (int) ($wiper->details[$field] ?? null),
            lengthSecond: null,
            countWipers: 1,
        );
    }

    private function extractForThreeWipers(KitData $kit): WiperLengthDTO
    {
        $main = $this->wiperBySort($kit, 0);
        $second = $this->wiperBySort($kit, 2);

        return new WiperLengthDTO(
            lengthMain: (int) ($main->details['length_main'] ?? null),
            lengthSecond: (int) ($second->details['length_main'] ?? null),
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
}
