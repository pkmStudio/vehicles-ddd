<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface PartSpecificationDataFactoryInterface
{
    /**
     * Собрать PartSpecificationData для specification двигателя.
     *
     * Шаги:
     * 1) Принять owner id и normalized details.
     * 2) Вернуть PartSpecificationData для create/update command.
     *
     * @param  array<string, mixed>  $details
     */
    public function make(int $engineId, array $details, ?int $id = null): PartSpecificationData;
}
