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
     * 1. Преобразует raw Excel row в typed `KitApplicabilityImportRowDTO`.
     * 2. Проверяет, что kit существует во внешнем Warehouse boundary.
     * 3. Разрешает vehicle modification по паре `ms_id` и `mod_id`.
     * 4. Делегирует запись связи применяемости command-у.
     */
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
     * Собирает DTO строки импорта и валидирует обязательные числовые идентификаторы.
     *
     * Шаги:
     * 1. Читает `ms_id`, `mod_id` и `kit_id` из первых трех Excel-колонок.
     * 2. Приводит значения к integer, как текущий контракт ручного XLSX import.
     * 3. Выбрасывает row validation exception, если любой обязательный id отсутствует.
     * 4. Возвращает typed DTO для дальнейшей проверки внешних справочников.
     *
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
