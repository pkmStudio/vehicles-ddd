<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestResultDTO;

/**
 * Порт сценария публикации локального файла импорта Vehicles.
 */
interface PublishLocalImportRequestUseCaseInterface
{
    /**
     * Проверяет запрос и публикует его во входящий import flow.
     */
    public function execute(LocalImportRequestDTO $request): LocalImportRequestResultDTO;
}
