<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;

/**
 * Разрешает CRM details template номенклатуры из Catalog-модели типа.
 */
final readonly class NomenclatureCrmTypeTemplateResolver
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

    /**
     * Возвращает строковое значение шаблона типа номенклатуры.
     */
    public function value(Type $type): ?string
    {
        return $this->resolve($type)?->value;
    }

    /**
     * Разрешает enum шаблона деталей для типа номенклатуры.
     */
    public function resolve(Type $type): ?NomenclatureDetailTemplateEnum
    {
        if (isset(self::TEMPLATE_BY_CHAR[$type->char])) {
            return self::TEMPLATE_BY_CHAR[$type->char];
        }

        return self::TEMPLATE_BY_ID[(int) $type->id] ?? null;
    }
}
