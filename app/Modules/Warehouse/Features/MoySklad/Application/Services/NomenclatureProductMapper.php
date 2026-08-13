<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Services;

use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductFolderMetaDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\MoySkladProductPayloadDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;

/**
 * Маппит Warehouse-номенклатуру в payload товара МойСклад.
 */
final readonly class NomenclatureProductMapper
{
    /**
     * Формирует payload для create/update товара МойСклад.
     */
    public function map(NomenclatureData $nomenclature, ?MoySkladProductFolderMetaDTO $productFolderMeta = null): MoySkladProductPayloadDTO
    {
        $productFolderMeta ??= MoySkladProductFolderMetaDTO::empty();

        $descriptionParts = array_filter([
            "Номенклатура #{$nomenclature->id}",
            $nomenclature->country ? "Страна: {$nomenclature->country}" : null,
            $nomenclature->color ? "Цвет: {$nomenclature->color}" : null,
            $nomenclature->brand?->name ? "Бренд: {$nomenclature->brand->name}" : null,
        ]);

        return new MoySkladProductPayloadDTO(
            name: $nomenclature->name,
            code: $nomenclature->partNumber,
            article: $nomenclature->partNumber,
            externalCode: $this->externalCodeForNomenclatureId((int) $nomenclature->id),
            description: implode('. ', $descriptionParts),
            weight: max((float) $nomenclature->weight, 0),
            productFolderMeta: $productFolderMeta,
        );
    }

    /**
     * Формирует стабильный externalCode для связки с локальной номенклатурой.
     */
    public function externalCodeForNomenclatureId(int $nomenclatureId): string
    {
        return "nomenclature:{$nomenclatureId}";
    }
}
