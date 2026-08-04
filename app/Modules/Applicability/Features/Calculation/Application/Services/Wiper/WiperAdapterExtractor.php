<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperAdapterExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperNomenclatureDetailsDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;

/**
 * Извлекает адаптеры дворников из состава Warehouse-набора.
 */
final readonly class WiperAdapterExtractor implements WiperAdapterExtractorInterface
{
    public function extract(KitData $kit, WiperKitPositionEnum $position): WiperAdaptersDTO
    {
        return match ($position) {
            WiperKitPositionEnum::FRONT => $this->extractByField($kit, WiperSideEnum::FRONT->adapterField()),
            WiperKitPositionEnum::BACK => $this->extractByField($kit, WiperSideEnum::BACK->adapterField()),
            WiperKitPositionEnum::UNIVERSAL => $this->extractUniversalAdapters($kit),
        };
    }

    private function extractUniversalAdapters(KitData $kit): WiperAdaptersDTO
    {
        $front = $this->rawAdapters($kit, WiperSideEnum::FRONT->adapterField());
        $rear = $this->rawAdapters($kit, WiperSideEnum::BACK->adapterField());

        return $this->mapAdapters(
            kit: $kit,
            allAdapters: array_merge($front['allAdapters'], $rear['allAdapters']),
            putAdapters: array_merge($front['putAdapters'], $rear['putAdapters']),
        );
    }

    private function extractByField(KitData $kit, string $adapterField): WiperAdaptersDTO
    {
        $adapters = $this->rawAdapters($kit, $adapterField);

        return $this->mapAdapters(
            kit: $kit,
            allAdapters: $adapters['allAdapters'],
            putAdapters: $adapters['putAdapters'],
        );
    }

    /**
     * @return array{allAdapters: array<int, array<int, string>>, putAdapters: array<int, array<int, string>>}
     */
    private function rawAdapters(KitData $kit, string $adapterField): array
    {
        $allAdapters = [];
        $putAdapters = [];

        foreach ($kit->nomenclatures as $nomenclature) {
            if ($nomenclature->template === NomenclatureDetailTemplateEnum::WIPER) {
                $allAdapters[] = WiperNomenclatureDetailsDTO::fromArray($nomenclature->details)->adaptersByField($adapterField);
            }

            if ($nomenclature->template === NomenclatureDetailTemplateEnum::WIPER_ADAPTER) {
                $adapters = WiperNomenclatureDetailsDTO::fromArray($nomenclature->details)
                    ->adapters(WiperSideEnum::FRONT);
                $allAdapters[] = $adapters;
                $putAdapters[] = $adapters;
            }
        }

        return [
            'allAdapters' => $allAdapters,
            'putAdapters' => $putAdapters,
        ];
    }

    /**
     * @param  array<int, array<int, string>>  $allAdapters
     * @param  array<int, array<int, string>>  $putAdapters
     */
    private function mapAdapters(KitData $kit, array $allAdapters, array $putAdapters): WiperAdaptersDTO
    {
        $allFlat = $allAdapters === [] ? [] : array_merge(...$allAdapters);
        $putFlat = $putAdapters === [] ? [] : array_merge(...$putAdapters);
        $allCounts = array_count_values($allFlat);

        $resultAdapters = [];
        foreach ($allCounts as $adapter => $count) {
            if ($count === $kit->quantityInPackage) {
                $resultAdapters[] = (string) $adapter;
            }
        }

        $putAdapters = array_values(array_intersect(
            array_values(array_unique($putFlat)),
            $resultAdapters,
        ));

        return new WiperAdaptersDTO(
            allAdapters: array_values(array_unique($resultAdapters)),
            putAdapters: $putAdapters,
        );
    }
}
