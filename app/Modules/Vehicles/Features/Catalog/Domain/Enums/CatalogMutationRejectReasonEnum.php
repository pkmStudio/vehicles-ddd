<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Enums;

/**
 * Перечисляет допустимые значения потока мутаций каталога.
 */
enum CatalogMutationRejectReasonEnum: string
{
    case AlreadyExists = 'already_exists';
    case NotFound = 'not_found';
    case ManufacturerNotFound = 'manufacturer_not_found';
    case ParentVehicleNotFound = 'parent_vehicle_not_found';
    case VehicleNotFound = 'vehicle_not_found';
    case OwnerNotFound = 'owner_not_found';
    case InvalidDetails = 'invalid_details';
}
