<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\ManufacturerEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerUpdated;

/**
 * Use-case: создать/обновить производителя из строки импорта (приведение к виду TD).
 */
final readonly class UpsertManufacturerFromRowService implements UpsertManufacturerFromRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-manufacturer-import';

    /**
     * Инициализирует порты сценария upsert производителя из TecDoc row.
     *
     * Шаги:
     * 1) Сохранить command записи производителя.
     * 2) Сохранить factory валидации и сборки `ManufacturerData`.
     * 3) Сохранить repository для проверки существующей записи.
     */
    public function __construct(
        private ManufacturerCommandInterface $command,
        private ManufacturerDataFactoryInterface $factory,
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /**
     * Создает или обновляет производителя из command import row.
     *
     * Шаги:
     * 1) Передать typed command DTO в factory.
     * 2) Валидировать и преобразовать строку в `ManufacturerData`.
     * 3) Найти существующего производителя по `mfa_id`.
     * 4) Выполнить create или update через command.
     * 5) Опубликовать catalog mutation event о создании или обновлении.
     * 6) Вернуть сохраненный `ManufacturerData`.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(ManufacturerCommandRowDTO $row): ManufacturerData
    {
        $data = $this->factory->makeFromCommandRow($row);
        $existing = $this->manufacturers->findByMfaId($data->mfaId);
        $manufacturer = $existing === null
            ? $this->command->create($data)
            : $this->command->update($data);

        $payload = new ManufacturerEventPayloadDTO(
            id: (int) $manufacturer->id,
            mfaId: $manufacturer->mfaId,
            name: $manufacturer->name,
            provider: $manufacturer->provider,
        );

        event($existing === null
            ? new ManufacturerCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload)
            : new ManufacturerUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $payload));

        return $manufacturer;
    }
}
