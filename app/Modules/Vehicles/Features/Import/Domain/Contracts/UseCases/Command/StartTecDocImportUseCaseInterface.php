<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\Command;

/**
 * Порт сценария запуска консольного каскада импорта TecDoc.
 */
interface StartTecDocImportUseCaseInterface
{
    /**
     * Запускает консольный импорт TecDoc с первого файла каскада.
     */
    public function execute(): void;
}
