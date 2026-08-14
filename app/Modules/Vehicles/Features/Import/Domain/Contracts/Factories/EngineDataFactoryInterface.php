<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineDataFactoryInterface
{
    /**
     * Собрать EngineData из строки импорта.
     *
     * Шаги:
     * 1) Принять normalized row DTO, собранный infrastructure import mapper.
     * 2) Провалидировать строку по режиму, указанному в row DTO.
     * 3) Нормализовать значения в EngineData.
     *
     * @throws ImportRowValidationException
     */
    public function make(EngineSheetRowDTO $row): EngineData;
}
