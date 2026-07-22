<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\UseCases\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\Command\StartTecDocImportUseCaseInterface;

/**
 * Запускает консольный каскад импорта TecDoc с файла производителей.
 */
final readonly class StartTecDocImportUseCase implements StartTecDocImportUseCaseInterface
{
    /**
     * Инициализирует зависимости сценария консольного импорта TecDoc.
     */
    public function __construct(
        private EngineModificationReadinessGateInterface $gate,
        private ManufacturerCommandImportInterface $manufacturers,
    ) {}

    /**
     * Запускает консольный импорт TecDoc.
     *
     * Шаги:
     * 1) Сбросить флаги синхронизации перед новым каскадом.
     * 2) Получить путь к файлу производителей из конфига.
     * 3) Запустить импорт производителей; дальнейший каскад пойдет через события.
     */
    public function execute(): void
    {
        $this->gate->reset();

        $path = storage_path((string) config(
            key: 'vehicles.import.command.manufacturers_path',
            default: 'vehicles/manufacturers.csv',
        ));

        $this->manufacturers->import($path);
    }
}
