<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;

/**
 * Собирает CRM list item DTO из SQL-проекции номенклатуры.
 */
final readonly class NomenclatureCrmListItemDTOFactory
{
    /**
     * Получает resolver details template для типа номенклатуры.
     *
     * Шаги:
     * 1) Принять NomenclatureCrmTypeTemplateResolver из DI container.
     * 2) Использовать resolver при сборке typeTemplate поля.
     */
    public function __construct(
        private NomenclatureCrmTypeTemplateResolver $templateResolver,
    ) {}

    /**
     * Собирает list item DTO номенклатуры для CRM таблицы.
     *
     * Шаги:
     * 1) Считать поля номенклатуры, бренда и типа из flat SQL projection.
     * 2) Нормализовать JSON/list fields в массивы.
     * 3) Разрешить template типа через resolver.
     * 4) Вернуть NomenclatureCrmListItemDTO для CRM response.
     */
    public function make(object $nomenclature): NomenclatureCrmListItemDTO
    {
        return new NomenclatureCrmListItemDTO(
            id: (int) $nomenclature->id,
            typeId: (int) $nomenclature->type_id,
            typeName: isset($nomenclature->type_name) ? (string) $nomenclature->type_name : null,
            typeChar: isset($nomenclature->type_char) ? (string) $nomenclature->type_char : null,
            typeTemplate: $this->templateResolver->value($nomenclature),
            brandId: (int) $nomenclature->brand_id,
            brandName: isset($nomenclature->brand_name) ? (string) $nomenclature->brand_name : null,
            brandChar: isset($nomenclature->brand_char) ? (string) $nomenclature->brand_char : null,
            name: (string) $nomenclature->name,
            country: (string) $nomenclature->country,
            partNumber: (string) $nomenclature->part_number,
            color: (string) $nomenclature->color,
            weight: (int) $nomenclature->weight,
            material: $this->listStringArray($nomenclature->material),
            vehicleType: $this->listStringArray($nomenclature->vehicle_type),
            quantityPak: (int) $nomenclature->quantity_pak,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: $this->jsonArray($nomenclature->details),
            createdAt: isset($nomenclature->created_at) ? (string) $nomenclature->created_at : null,
            updatedAt: isset($nomenclature->updated_at) ? (string) $nomenclature->updated_at : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function listStringArray(mixed $value): array
    {
        $decoded = $this->jsonArray($value);

        return array_values(array_map('strval', $decoded));
    }
}
