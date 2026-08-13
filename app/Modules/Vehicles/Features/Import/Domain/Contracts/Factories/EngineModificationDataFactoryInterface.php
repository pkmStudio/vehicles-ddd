<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;

interface EngineModificationDataFactoryInterface
{
    /**
     * Собрать EngineModificationData из сырой строки импорта.
     *
     * Шаги:
     * 1) Провалидировать ключи engine/modification связи.
     * 2) Нормализовать значения в EngineModificationData.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    public function make(array $row): EngineModificationData;
}
