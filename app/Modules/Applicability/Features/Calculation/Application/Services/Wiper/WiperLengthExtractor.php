<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperLengthExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperNomenclatureDetailsDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Exceptions\InvalidWiperKitDataException;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

final readonly class WiperLengthExtractor implements WiperLengthExtractorInterface
{
    /**
     * Извлекает длины щеток из комплекта дворников по количеству в упаковке.
     *
     * Шаги:
     * 1. Для одиночной щетки читает один размер по позиции.
     * 2. Для комплекта из трех щеток читает main и rear по сортировке состава.
     * 3. Для остальных комплектов применяет алгоритм пары щеток.
     */
    public function extract(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO
    {
        return match ($kit->quantityInPackage) {
            1 => $this->extractForOneWiper($kit, $position),
            3 => $this->extractForThreeWipers($kit),
            default => $this->extractForTwoWipers($kit, $position),
        };
    }

    /**
     * Извлекает длины для комплекта из двух дворников.
     *
     * Шаги:
     * 1. Берет первую WIPER-номенклатуру с sort `0`.
     * 2. Если первая SKU содержит две щетки, читает main и second length из ее details.
     * 3. Иначе берет вторую WIPER-номенклатуру с sort `1`.
     * 4. Выбирает длину каждой щетки по front/back position.
     * 5. Возвращает DTO с количеством щеток `2`.
     */
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
            lengthMain: $this->requiredLength($lengthMain, 'length_main'),
            lengthSecond: $this->requiredLength($lengthSecond, 'length_second'),
            countWipers: 2,
        );
    }

    /**
     * Извлекает длину для одиночной щетки.
     *
     * Шаги:
     * 1. Берет WIPER-номенклатуру с sort `0`.
     * 2. Читает ее typed details.
     * 3. Выбирает front или rear length по позиции комплекта.
     * 4. Возвращает DTO с одной щеткой и пустой second length.
     */
    private function extractForOneWiper(KitData $kit, WiperKitPositionEnum $position): WiperLengthDTO
    {
        $wiper = $this->wiperBySort($kit, 0);
        $details = $this->wiperDetails($wiper);

        return new WiperLengthDTO(
            lengthMain: $this->requiredLength($this->lengthForPosition($details, $position), 'length_main'),
            lengthSecond: null,
            countWipers: 1,
        );
    }

    /**
     * Извлекает длины для комплекта из трех дворников.
     *
     * Шаги:
     * 1. Берет основную WIPER-номенклатуру с sort `0`.
     * 2. Берет rear WIPER-номенклатуру с sort `2`.
     * 3. Читает main length у обеих позиций состава.
     * 4. Возвращает DTO с количеством щеток `3`.
     */
    private function extractForThreeWipers(KitData $kit): WiperLengthDTO
    {
        $main = $this->wiperBySort($kit, 0);
        $second = $this->wiperBySort($kit, 2);

        return new WiperLengthDTO(
            lengthMain: $this->requiredLength($this->wiperDetails($main)->lengthMain(), 'length_main'),
            lengthSecond: $this->requiredLength($this->wiperDetails($second)->lengthMain(), 'length_main'),
            countWipers: 3,
        );
    }

    /**
     * Находит WIPER-номенклатуру комплекта по позиции sort.
     *
     * Шаги:
     * 1. Обходит состав комплекта.
     * 2. Возвращает только WIPER template с нужным sort.
     * 3. Если позиция отсутствует, выбрасывает domain exception с kit id и sort.
     */
    private function wiperBySort(KitData $kit, int $sort): NomenclatureData
    {
        foreach ($kit->nomenclatures as $nomenclature) {
            if ($nomenclature->template === NomenclatureDetailTemplateEnum::WIPER && $nomenclature->sort === $sort) {
                return $nomenclature;
            }
        }

        throw InvalidWiperKitDataException::missingWiperBySort($kit->id, $sort);
    }

    /**
     * Преобразует details WIPER-номенклатуры в typed DTO.
     *
     * Шаги:
     * 1. Берет raw details из `NomenclatureData`.
     * 2. Собирает `WiperNomenclatureDetailsDTO` для безопасного чтения размеров.
     */
    private function wiperDetails(NomenclatureData $nomenclature): WiperNomenclatureDetailsDTO
    {
        return WiperNomenclatureDetailsDTO::fromArray($nomenclature->details);
    }

    /**
     * Выбирает длину щетки из details по позиции комплекта.
     *
     * Шаги:
     * 1. Для back-комплекта возвращает rear length.
     * 2. Для front/universal-комплекта возвращает main length.
     */
    private function lengthForPosition(WiperNomenclatureDetailsDTO $details, WiperKitPositionEnum $position): ?int
    {
        return $position === WiperKitPositionEnum::BACK ? $details->lengthRear() : $details->lengthMain();
    }

    /**
     * Возвращает обязательную длину или выбрасывает понятную domain error.
     */
    private function requiredLength(?int $length, string $field): int
    {
        if ($length === null) {
            throw InvalidWiperKitDataException::missingWiperLength($field);
        }

        return $length;
    }
}
