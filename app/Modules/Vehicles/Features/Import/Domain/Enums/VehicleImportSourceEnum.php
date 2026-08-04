<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Enums;

/**
 * Перечисляет источники записи автомобиля в import workflow.
 */
enum VehicleImportSourceEnum: string
{
    case TecDocCommand = 'tecdoc_command';
    case ManualSheet = 'manual_sheet';
}
