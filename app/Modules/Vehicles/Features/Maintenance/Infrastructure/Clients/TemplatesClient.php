<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Получает public client Templates, владеющий правилами разбора details.
     *
     * Шаги:
     * 1. Принять public Templates client из DI container.
     * 2. Сохранить adapter dependency для Maintenance feature.
     */
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    /**
     * Делегирует разрезание vehicle wiper details в Templates module.
     *
     * Шаги:
     * 1. Передать details и id исходной PartSpecification в Templates public client.
     * 2. Вернуть side-записи без изменения их структуры внутри Maintenance feature.
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array
    {
        return $this->templates->splitVehicleWiperSpecification($details, $partSpecificationId);
    }
}
