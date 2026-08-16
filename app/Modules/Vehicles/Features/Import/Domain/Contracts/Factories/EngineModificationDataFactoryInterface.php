<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;

interface EngineModificationDataFactoryInterface
{
    /**
     * Собрать EngineModificationData из normalized row DTO.
     *
     * Шаги:
     * 1) Провалидировать ключи engine/modification связи.
     * 2) Нормализовать значения в EngineModificationData.
     *
     * @throws ImportRowValidationException
     */
    public function make(EngineModificationRowDTO $row): EngineModificationData;
}
