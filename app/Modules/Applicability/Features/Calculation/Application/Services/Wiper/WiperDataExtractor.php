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
    /**
     * Получает специализированные extractors длины и адаптеров дворников.
     *
     * Шаги:
     * 1. Сохраняет extractor длины, который учитывает количество щеток в комплекте.
     * 2. Сохраняет extractor адаптеров, который учитывает front/back/universal позицию.
     */
    public function __construct(
        private WiperLengthExtractorInterface $lengthExtractor,
        private WiperAdapterExtractorInterface $adapterExtractor,
    ) {}

    /**
     * Определяет позицию комплекта дворников: front, back или universal.
     *
     * Шаги:
     * 1. Обходит номенклатуры комплекта и берет position только у WIPER template.
     * 2. Если ни одна позиция не найдена, выбрасывает domain exception с kit id.
     * 3. Если среди позиций есть back, возвращает `BACK`.
     * 4. Для front-кандидата извлекает длины и адаптеры.
     * 5. Считает комплект universal, если длины равны и есть hook `H/H83`, либо это одиночный hook.
     * 6. В остальных случаях возвращает `FRONT`.
     */
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

    /**
     * Извлекает расчетные длины дворников для заданной или вычисленной позиции.
     *
     * Шаги:
     * 1. Если position не передана, определяет ее через `extractPosition()`.
     * 2. Делегирует разбор длины специализированному length extractor-у.
     */
    public function extractLength(KitData $kit, ?WiperKitPositionEnum $position = null): WiperLengthDTO
    {
        return $this->lengthExtractor->extract(
            kit: $kit,
            position: $position ?? $this->extractPosition($kit),
        );
    }

    /**
     * Извлекает расчетные адаптеры дворников для заданной или вычисленной позиции.
     *
     * Шаги:
     * 1. Если position не передана, определяет ее через `extractPosition()`.
     * 2. Делегирует разбор adapter fields специализированному adapter extractor-у.
     */
    public function extractAdapters(KitData $kit, ?WiperKitPositionEnum $position = null): WiperAdaptersDTO
    {
        return $this->adapterExtractor->extract(
            kit: $kit,
            position: $position ?? $this->extractPosition($kit),
        );
    }
}
