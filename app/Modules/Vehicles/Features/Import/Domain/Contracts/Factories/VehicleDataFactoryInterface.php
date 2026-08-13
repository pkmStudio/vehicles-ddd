<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleDataFactoryInterface
{
    /**
     * Собрать VehicleData из сырой строки импорта.
     *
     * Шаги:
     * 1) Провалидировать vehicle-поля строки.
     * 2) Нормализовать значения в VehicleData.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    public function make(array $row): VehicleData;
}
