<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums;

/**
 * Шаблон заполнения details у Nomenclature (Warehouse). Отдельный enum от `DetailTemplateEnum`
 * (тот — про PartSpecification/Vehicles): даже одноимённые по сути товары (например Wiper) здесь
 * описывают собственные характеристики товара, а не потребность конкретного ТС — разные формы,
 * разные Data-классы, поэтому и разный диспетчеризационный enum. Сборка — забота
 * `Templates\Application\Factories\DetailsDataFactory::buildFromRow()`, не самого enum'а.
 */
enum NomenclatureDetailTemplateEnum: string
{
    case BRAKE_PADS = 'brakePads';
    case SPARK_PLUGS = 'sparkPlugs';
    case WIPER = 'wiper';
    case OIL_FILTER = 'oilFilter';
    case AIR_FILTER = 'airFilter';
    case CABIN_FILTER = 'cabinFilter';
    case WIPER_ADAPTER = 'wiperAdapter';
    case TIMING_BELT = 'timingBelt';
    case V_BELT = 'generic';
    case WHEEL_HUB_BEARING = 'wheelHubBearing';
    case WHEEL_HUB = 'wheelHub';
    case TIE_ROD_END = 'tieRodEnd';
    case TIE_ROD = 'tieRod';
    case STABILIZER_LINK = 'stabilizerLink';
    case BALL_JOINT = 'ballJoint';
    case CV_JOINT = 'cvJoint';
    case POLY_V_BELT = 'polyVBelt';
}
