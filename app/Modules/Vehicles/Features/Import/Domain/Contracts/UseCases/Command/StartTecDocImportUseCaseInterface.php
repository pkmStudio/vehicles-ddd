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
     *
     * Шаги:
     * 1) Сбросить readiness state каскада.
     * 2) Запустить первый command import adapter.
     * 3) Дальнейшие шаги каскада продолжить через events/listeners.
     */
    public function execute(): void;
}
