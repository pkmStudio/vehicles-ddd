<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

/**
 * DTO входящей команды на массовое удаление автомобилей.
 */
final readonly class VehicleBulkDeleteRequestDTO
{
    /**
     * Получает автора операции, correlation id и список ms_id автомобилей.
     *
     * @param  list<int>  $msIds
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $msIds,
    ) {}
}
