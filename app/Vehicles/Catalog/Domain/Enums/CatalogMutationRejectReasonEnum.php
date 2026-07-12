<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

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
    case DeleteBlocked = 'delete_blocked';
}
