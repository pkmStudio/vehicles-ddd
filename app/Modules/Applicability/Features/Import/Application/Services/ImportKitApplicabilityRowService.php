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
    public function __construct(
        private WarehouseKitClientInterface $kits,
        private VehiclesModificationClientInterface $modifications,
        private KitApplicabilityCommandInterface $command,
    ) {}

    public function importFromRow(array $row): void
    {
        $dto = $this->makeRow($row);

        if (! $this->kits->exists($dto->kitId)) {
            throw new ImportRowValidationException("Кит с ID {$dto->kitId} не найден в системе.");
        }

        $modificationId = $this->modifications->resolveByMsAndModId($dto->msId, $dto->modId);

        $this->command->saveImportedModificationTarget(
            kitId: $dto->kitId,
            modificationId: $modificationId,
        );
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function makeRow(array $row): KitApplicabilityImportRowDTO
    {
        $msId = (int) ($row[0] ?? 0);
        $modId = (int) ($row[1] ?? 0);
        $kitId = (int) ($row[2] ?? 0);

        if ($msId === 0 || $modId === 0 || $kitId === 0) {
            throw new ImportRowValidationException('Строка применяемости должна содержать ms_id, mod_id и kit_id.');
        }

        return new KitApplicabilityImportRowDTO($msId, $modId, $kitId);
    }
}
