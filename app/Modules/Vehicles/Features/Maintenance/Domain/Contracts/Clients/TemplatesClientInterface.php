<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients;

interface TemplatesClientInterface
{
    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     *
     * Шаги:
     * 1. Принять raw details legacy wiper specification.
     * 2. Вернуть нормализованные side-записи, готовые для отдельных PartSpecification.
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array;
}
