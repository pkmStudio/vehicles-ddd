<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineDataFactoryInterface
{
    /**
     * Собрать EngineData из TecDoc command-строки импорта.
     *
     * Шаги:
     * 1) Принять normalized TD row DTO, собранный infrastructure import mapper.
     * 2) Провалидировать строгий TecDoc-контракт строки.
     * 3) Нормализовать значения в EngineData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(EngineTdRowDTO $row): EngineData;

    /**
     * Собрать EngineData из manager/external sheet-строки импорта.
     *
     * Шаги:
     * 1) Принять normalized sheet row DTO, собранный infrastructure import mapper.
     * 2) Провалидировать поля, обязательные для записи engine.
     * 3) Нормализовать значения в EngineData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromSheetRow(EngineSheetRowDTO $row): EngineData;
}
