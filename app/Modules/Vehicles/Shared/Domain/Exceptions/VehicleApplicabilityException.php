<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Exceptions;

use DomainException;

/**
 * Ошибка резолва данных Vehicles для расчета или импорта применяемости.
 */
final class VehicleApplicabilityException extends DomainException {}
