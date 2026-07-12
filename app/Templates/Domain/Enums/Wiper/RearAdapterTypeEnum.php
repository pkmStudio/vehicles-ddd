<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\Wiper;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Тип крепления задних дворников. */
enum RearAdapterTypeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case RA = 'RA';
    case RB = 'RB';
    case RC = 'RC';
    case RD = 'RD';
    case RE = 'RE';
    case RG = 'RG';
    case RH = 'RH';
    case RJ = 'RJ';
    case RK = 'RK';
    case RL = 'RL';
    case RM = 'RM';
    case RN = 'RN';
    case RP = 'RP';
    case RQ = 'RQ';
    case RR = 'RR';
    case RS = 'RS';
    case RT = 'RT';
    case RU = 'RU';
    case RV = 'RV';
    case RW = 'RW';
    case RX = 'RX';
    case RY = 'RY';
    case RZ = 'RZ';
    case RCH = 'RCH';
    case RDB = 'RDB';
    case REN = 'REN';
    case RES = 'RES';
    case RFB = 'RFB';
    case RFJ = 'RFJ';
    case RGE = 'RGE';
    case RJP = 'RJP';
    case RPL = 'RPL';
    case RRT = 'RRT';
    case RRU = 'RRU';
    case RRX = 'RRX';
    case RSQ = 'RSQ';
    case RST = 'RST';
    case RTG = 'RTG';
    case RVL = 'RVL';
    case RFW = 'RFW';
    case H = 'Крючок (Hook / J-Hook)';
    case H83 = 'Крючок (Hook / J-Hook) 8x3';
}
