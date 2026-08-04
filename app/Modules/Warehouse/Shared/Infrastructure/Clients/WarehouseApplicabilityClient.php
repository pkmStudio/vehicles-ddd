<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Infrastructure\Clients;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseNomenclatureForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseTypeForApplicabilityDTO;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Публичный синхронный клиент Warehouse для расчета применяемости.
 */
final readonly class WarehouseApplicabilityClient implements WarehouseApplicabilityClientInterface
{
    private const array TEMPLATE_BY_CHAR = [
        'BP' => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        'SP' => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
        'WB' => NomenclatureDetailTemplateEnum::WIPER,
        'OF' => NomenclatureDetailTemplateEnum::OIL_FILTER,
        'AF' => NomenclatureDetailTemplateEnum::AIR_FILTER,
        'CF' => NomenclatureDetailTemplateEnum::CABIN_FILTER,
        'AW' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        'TB' => NomenclatureDetailTemplateEnum::TIMING_BELT,
        'VB' => NomenclatureDetailTemplateEnum::V_BELT,
        'HB' => NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING,
        'WH' => NomenclatureDetailTemplateEnum::WHEEL_HUB,
        'TE' => NomenclatureDetailTemplateEnum::TIE_ROD_END,
        'TR' => NomenclatureDetailTemplateEnum::TIE_ROD,
        'SL' => NomenclatureDetailTemplateEnum::STABILIZER_LINK,
        'BJ' => NomenclatureDetailTemplateEnum::BALL_JOINT,
        'CV' => NomenclatureDetailTemplateEnum::CV_JOINT,
        'SB' => NomenclatureDetailTemplateEnum::POLY_V_BELT,
    ];

    private const array TEMPLATE_BY_ID = [
        1 => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        2 => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
        3 => NomenclatureDetailTemplateEnum::WIPER,
        4 => NomenclatureDetailTemplateEnum::OIL_FILTER,
        5 => NomenclatureDetailTemplateEnum::AIR_FILTER,
        6 => NomenclatureDetailTemplateEnum::CABIN_FILTER,
        7 => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        8 => NomenclatureDetailTemplateEnum::TIMING_BELT,
        9 => NomenclatureDetailTemplateEnum::V_BELT,
        10 => NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING,
        11 => NomenclatureDetailTemplateEnum::WHEEL_HUB,
        12 => NomenclatureDetailTemplateEnum::TIE_ROD_END,
        13 => NomenclatureDetailTemplateEnum::TIE_ROD,
        14 => NomenclatureDetailTemplateEnum::STABILIZER_LINK,
        15 => NomenclatureDetailTemplateEnum::BALL_JOINT,
        16 => NomenclatureDetailTemplateEnum::CV_JOINT,
        17 => NomenclatureDetailTemplateEnum::POLY_V_BELT,
    ];

    private const array TEMPLATE_BY_NAME = [
        'КОЛОДКИ' => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        'КОЛОДКИ ТОРМОЗНЫЕ' => NomenclatureDetailTemplateEnum::BRAKE_PADS,
        'СВЕЧИ ЗАЖИГАНИЯ' => NomenclatureDetailTemplateEnum::SPARK_PLUGS,
        'ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER,
        'ЩЕТКА СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER,
        'ФИЛЬТР МАСЛЯНЫЙ' => NomenclatureDetailTemplateEnum::OIL_FILTER,
        'ФИЛЬТР ВОЗДУШНЫЙ' => NomenclatureDetailTemplateEnum::AIR_FILTER,
        'ФИЛЬТР САЛОННЫЙ' => NomenclatureDetailTemplateEnum::CABIN_FILTER,
        'АДАПТЕР СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        'АДАПТЕР ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ' => NomenclatureDetailTemplateEnum::WIPER_ADAPTER,
        'РЕМЕНЬ ГРМ' => NomenclatureDetailTemplateEnum::TIMING_BELT,
        'РЕМЕНЬ КЛИНОВОЙ' => NomenclatureDetailTemplateEnum::V_BELT,
        'ПОДШИПНИК СТУПИЦЫ' => NomenclatureDetailTemplateEnum::WHEEL_HUB_BEARING,
        'СТУПИЦА' => NomenclatureDetailTemplateEnum::WHEEL_HUB,
        'НАКОНЕЧНИК РУЛЕВОЙ ТЯГИ' => NomenclatureDetailTemplateEnum::TIE_ROD_END,
        'РУЛЕВАЯ ТЯГА' => NomenclatureDetailTemplateEnum::TIE_ROD,
        'СТОЙКА СТАБИЛИЗАТОРА' => NomenclatureDetailTemplateEnum::STABILIZER_LINK,
        'ШАРОВАЯ ОПОРА' => NomenclatureDetailTemplateEnum::BALL_JOINT,
        'ШРУС' => NomenclatureDetailTemplateEnum::CV_JOINT,
        'РЕМЕНЬ ПОЛИКЛИНОВОЙ' => NomenclatureDetailTemplateEnum::POLY_V_BELT,
    ];

    /**
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeApplicabilityKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        $query = DB::table('kits')->where('is_active', true);

        if ($kitId !== null) {
            $query->where('id', $kitId);
        }

        foreach ($query->lazyById($chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    public function kitExists(int $kitId): bool
    {
        return DB::table('kits')->where('id', $kitId)->exists();
    }

    private function mapKit(stdClass $kit): WarehouseKitForApplicabilityDTO
    {
        return new WarehouseKitForApplicabilityDTO(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $this->nomenclatures((int) $kit->id),
            type: $this->type((int) $kit->type_id),
        );
    }

    /**
     * @return array<int, WarehouseNomenclatureForApplicabilityDTO>
     */
    private function nomenclatures(int $kitId): array
    {
        return DB::table('nomenclatures')
            ->join('kit_nomenclature', 'kit_nomenclature.nomenclature_id', '=', 'nomenclatures.id')
            ->where('kit_nomenclature.kit_id', $kitId)
            ->orderBy('kit_nomenclature.sort')
            ->select([
                'nomenclatures.id',
                'nomenclatures.type_id',
                'nomenclatures.quantity_in_pak',
                'nomenclatures.details',
                'kit_nomenclature.sort',
            ])
            ->get()
            ->map(fn (stdClass $nomenclature): WarehouseNomenclatureForApplicabilityDTO => new WarehouseNomenclatureForApplicabilityDTO(
                id: (int) $nomenclature->id,
                typeId: (int) $nomenclature->type_id,
                quantityInPak: (int) $nomenclature->quantity_in_pak,
                details: $this->jsonArray($nomenclature->details),
                sort: (int) $nomenclature->sort,
                type: $this->type((int) $nomenclature->type_id),
            ))
            ->all();
    }

    private function type(int $typeId): ?WarehouseTypeForApplicabilityDTO
    {
        $type = DB::table('types')->where('id', $typeId)->first(['id', 'name', 'char']);

        if ($type === null) {
            return null;
        }

        return new WarehouseTypeForApplicabilityDTO(
            id: (int) $type->id,
            name: (string) $type->name,
            char: $type->char === null ? null : (string) $type->char,
            template: $this->resolveTypeTemplate($type),
        );
    }

    private function resolveTypeTemplate(stdClass $type): ?NomenclatureDetailTemplateEnum
    {
        $char = $type->char === null ? null : mb_strtoupper(trim((string) $type->char));

        if ($char !== null && isset(self::TEMPLATE_BY_CHAR[$char])) {
            return self::TEMPLATE_BY_CHAR[$char];
        }

        $id = (int) $type->id;

        if (isset(self::TEMPLATE_BY_ID[$id])) {
            return self::TEMPLATE_BY_ID[$id];
        }

        $name = mb_strtoupper(trim((string) $type->name));

        return self::TEMPLATE_BY_NAME[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
