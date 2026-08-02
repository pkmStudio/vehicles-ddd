<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle;

/**
 * Use case port detail CRM API Vehicles.
 */
interface ShowVehicleForCrmUseCaseInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function execute(int $id): ?array;
}
