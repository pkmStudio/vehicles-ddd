<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\NomenclatureData;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\TypeData;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Kit;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Nomenclature;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Models\Type;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    private const array TEMPLATE_BY_CHAR = [
        'BP' => NomenclatureDetailTemplateEnum::BRAKE_PADS,
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

    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        $query = Kit::query()
            ->with(['type', 'nomenclatures.type'])
            ->where('is_active', true)
            ->when($kitId !== null, static fn ($query) => $query->where('id', $kitId));

        foreach ($query->lazyById($chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    private function mapKit(Kit $kit): KitData
    {
        $type = $this->mapType($kit->type);

        return new KitData(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $kit->nomenclatures
                ->map(fn (Nomenclature $nomenclature): NomenclatureData => $this->mapNomenclature($nomenclature))
                ->all(),
            type: $type,
            template: $type?->template,
        );
    }

    private function mapNomenclature(Nomenclature $nomenclature): NomenclatureData
    {
        $type = $this->mapType($nomenclature->type);

        return new NomenclatureData(
            typeId: (int) $nomenclature->type_id,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: (array) ($nomenclature->details ?? []),
            id: (int) $nomenclature->id,
            sort: (int) ($nomenclature->pivot?->sort ?? 0),
            type: $type,
            template: $type?->template,
        );
    }

    private function mapType(?Type $type): ?TypeData
    {
        if ($type === null) {
            return null;
        }

        return new TypeData(
            name: (string) $type->name,
            char: $type->char,
            id: (int) $type->id,
            template: $this->template($type),
        );
    }

    private function template(Type $type): ?NomenclatureDetailTemplateEnum
    {
        $char = $type->char === null ? null : mb_strtoupper(trim($type->char));
        if ($char !== null && isset(self::TEMPLATE_BY_CHAR[$char])) {
            return self::TEMPLATE_BY_CHAR[$char];
        }

        if (isset(self::TEMPLATE_BY_ID[(int) $type->id])) {
            return self::TEMPLATE_BY_ID[(int) $type->id];
        }

        $name = mb_strtoupper(trim((string) $type->name));

        return self::TEMPLATE_BY_NAME[$name] ?? null;
    }
}
