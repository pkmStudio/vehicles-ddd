<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Contracts\UseCases;

use App\Modules\Shared\Domain\DTOs\LocalImportRequestDTO;
use App\Modules\Shared\Domain\DTOs\LocalImportRequestResultDTO;

/**
 * Порт сценария публикации локального файла импорта.
 */
interface PublishLocalImportRequestUseCaseInterface
{
    /**
     * Проверяет запрос и публикует его во входящий import flow.
     */
    public function execute(LocalImportRequestDTO $request): LocalImportRequestResultDTO;
}
