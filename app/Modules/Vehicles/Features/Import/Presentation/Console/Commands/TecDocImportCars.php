<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\Command\StartTecDocImportUseCaseInterface;
use Illuminate\Console\Command;

/**
 * Консольная точка входа для запуска каскадного импорта TecDoc.
 */
final class TecDocImportCars extends Command
{
    protected $signature = 'app:tecDoc-import-cars';

    protected $description = 'Приводит данные ТС к виду ТекДок';

    /**
     * Запускает Application-сценарий импорта TecDoc.
     *
     * Шаги:
     * 1. Передать выполнение каскадного TecDoc import в Application use case.
     * 2. Сообщить оператору, что сценарий поставил дальнейшую работу в очередь.
     * 3. Вернуть successful console exit code.
     */
    public function handle(StartTecDocImportUseCaseInterface $useCase): int
    {
        $useCase->execute();

        $this->info('Команда запустилась и отправило исполнение в очередь');

        return self::SUCCESS;
    }
}
