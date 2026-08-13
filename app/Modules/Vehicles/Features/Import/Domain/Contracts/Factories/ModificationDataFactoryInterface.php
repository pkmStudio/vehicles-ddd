<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface ModificationDataFactoryInterface
{
    /**
     * Собрать ModificationData из сырой строки импорта.
     *
     * Шаги:
     * 1) Провалидировать modification-поля строки.
     * 2) Нормализовать значения в ModificationData.
     *
     * @param  array<string, mixed>  $row
     *
     * @throws ImportRowValidationException
     */
    public function make(array $row): ModificationData;
}
