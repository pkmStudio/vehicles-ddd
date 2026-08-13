<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\Services;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\ImportKitApplicabilityRowServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\KitApplicabilityImportRowDTO;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;

final readonly class ImportKitApplicabilityRowService implements ImportKitApplicabilityRowServiceInterface
{
    /**
     * Получает порты, нужные для записи одной строки применяемости.
     *
     * Шаги:
     * 1. Сохраняет client проверки существования kit в Warehouse.
     * 2. Сохраняет client разрешения пары ms_id/mod_id в modification id Vehicles.
     * 3. Сохраняет command, который пишет imported applicability target.
     */
    public function __construct(
        private WarehouseKitClientInterface $kits,
        private VehiclesModificationClientInterface $modifications,
        private KitApplicabilityCommandInterface $command,
    ) {}

    /**
     * Импортирует одну строку XLSX применяемости комплекта к модификации.
     *
     * Шаги:
     * 1. Проверяет, что kit существует во внешнем Warehouse boundary.
     * 2. Разрешает vehicle modification по паре `ms_id` и `mod_id`.
     * 3. Делегирует запись связи применяемости command-у.
     */
    public function importFromRow(KitApplicabilityImportRowDTO $row): void
    {
        if (! $this->kits->exists($row->kitId)) {
            throw new ImportRowValidationException("Кит с ID {$row->kitId} не найден в системе.");
        }

        $modificationId = $this->modifications->resolveByMsAndModId($row->msId, $row->modId);

        $this->command->saveImportedModificationTarget(
            kitId: $row->kitId,
            modificationId: $modificationId,
        );
    }
}
