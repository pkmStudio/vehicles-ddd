<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Application\Services;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Export\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Export\Domain\ModelData\TypeData;

/**
 * Заменяет старую таблицу detail_templates для Nomenclature: шаблон определяется по стабильному
 * коду types.char, с fallback на исторические id/name из dan-center.
 */
final readonly class TypeTemplateResolver implements TypeTemplateResolverInterface
{
    private const array BY_CHAR = [
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

    private const array BY_ID = [
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

    private const array BY_NAME = [
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
     * Определяет шаблон details по char, затем по историческому id, затем по имени типа.
     */
    public function resolve(TypeData $type): ?NomenclatureDetailTemplateEnum
    {
        $char = $type->char === null ? null : mb_strtoupper(trim($type->char));
        if ($char !== null && isset(self::BY_CHAR[$char])) {
            return self::BY_CHAR[$char];
        }

        if ($type->id !== null && isset(self::BY_ID[$type->id])) {
            return self::BY_ID[$type->id];
        }

        $name = mb_strtoupper(trim($type->name));

        return self::BY_NAME[$name] ?? null;
    }
}
