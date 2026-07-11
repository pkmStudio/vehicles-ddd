<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\UseCases\External;

use App\Vehicles\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Сценарий принятия внешнего запроса на импорт файла.
 */
interface StartExternalFileImportUseCaseInterface
{
    /**
     * Проверить идемпотентность, подготовить файл и запустить нужный импортный адаптер.
     */
    public function execute(ExternalImportFileRequestDTO $request): void;
}
