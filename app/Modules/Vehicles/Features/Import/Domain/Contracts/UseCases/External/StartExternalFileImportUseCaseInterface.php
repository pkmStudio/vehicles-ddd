<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

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
