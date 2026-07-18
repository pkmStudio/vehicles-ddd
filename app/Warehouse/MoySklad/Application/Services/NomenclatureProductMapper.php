<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Application\Services;

use App\Warehouse\MoySklad\Domain\ModelData\NomenclatureData;

/**
 * Маппит Warehouse-номенклатуру в payload товара МойСклад.
 */
final readonly class NomenclatureProductMapper
{
    /**
     * Формирует payload для create/update товара МойСклад.
     *
     * @param  array<string, mixed>  $productFolderMeta
     * @return array<string, mixed>
     */
    public function map(NomenclatureData $nomenclature, array $productFolderMeta = []): array
    {
        $descriptionParts = array_filter([
            "Номенклатура #{$nomenclature->id}",
            $nomenclature->country ? "Страна: {$nomenclature->country}" : null,
            $nomenclature->color ? "Цвет: {$nomenclature->color}" : null,
            $nomenclature->brand?->name ? "Бренд: {$nomenclature->brand->name}" : null,
        ]);

        $payload = [
            'name' => $nomenclature->name,
            'code' => $nomenclature->partNumber,
            'article' => $nomenclature->partNumber,
            'externalCode' => $this->externalCodeForNomenclatureId((int) $nomenclature->id),
            'description' => implode('. ', $descriptionParts),
            'weight' => max((float) $nomenclature->weight, 0),
        ];

        if ($productFolderMeta !== []) {
            $payload['productFolder'] = $productFolderMeta;
        }

        return $payload;
    }

    /**
     * Формирует стабильный externalCode для связки с локальной номенклатурой.
     */
    public function externalCodeForNomenclatureId(int $nomenclatureId): string
    {
        return "nomenclature:{$nomenclatureId}";
    }
}
