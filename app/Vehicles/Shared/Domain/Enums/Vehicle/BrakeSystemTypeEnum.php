<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

enum BrakeSystemTypeEnum: string
{
    case DRUM = 'Барабанный тормозной механизм';
    case DISC = 'Дисковой тормозной механизм';
    case DISCS_DRUMS = 'Диски/барабаны';
}
