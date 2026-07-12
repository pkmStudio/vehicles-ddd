<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

enum CarcaseTypeEnum: string
{
    // Легковые автомобили - Седаны
    case SALOON = 'Saloon';
    case LIFTBACK = 'Liftback';
    case HARDTOP = 'Hardtop';

    // Легковые автомобили - Хэтчбеки
    case HATCHBACK = 'Hatchback';
    case HATCHBACK_GTC = 'Hatchback GTC';
    case SPORTBACK = 'Sportback';

    // Легковые автомобили - Универсалы/Купе
    case COUPE = 'Coupe';
    case ESTATE = 'Estate';
    case AVANT_COMBI = 'Avant Combi';
    case COMBI = 'Combi';
    case KOMBI = 'Kombi';
    case BREAK = 'Break';
    case BREAK_SW = 'Break SW';
    case SW = 'SW';
    case WAGON = 'Wagon';
    case S_WAGON = 'S-Wagon';
    case TURNIER = 'Turnier';
    case TOURER = 'Tourer';
    case GRANDTOUR = 'Grandtour';
    case SPORT_TOURER = 'Sport Tourer';
    case CW = 'CW';
    case VARIANT = 'Variant';

    // Внедорожники и кроссоверы
    case SUV = 'Suv';
    case OFF_ROAD = 'Off-Road';

    // Коммерческий транспорт - Минивэны и микроавтобусы
    case MVP = 'Mpv';
    case VAN = 'Van';
    case BUS = 'Bus';

    // Коммерческий транспорт - Грузовики и пикапы
    case PICKUP = 'Pickup';
    case TRUCK_TRACTOR = 'Truck Tractor';
    case DUMP_TRUCK = 'Dump Truck';
    case CONCRETE_MIXER = 'Concrete Mixer';
    case BUS_CHASSIS = 'Bus Chassis';
    case CAB_WITH_ENGINE = 'Cab With Engine';

    // Специальная техника
    case TRACTOR = 'Tractor';
    case MUNICIPAL_VEHICLE = 'Municipal Vehicle';

    // Мототехника — TecDoc не даёт "Тип кузова" для VehicleTypeEnum::MB, подставляется по
    // правилу (см. VehicleDataFactory)
    case MOTORCYCLE = 'Motorcycle';
}
