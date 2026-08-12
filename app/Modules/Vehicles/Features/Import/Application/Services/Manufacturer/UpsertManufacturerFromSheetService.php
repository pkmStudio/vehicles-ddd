<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerUpdated;

/**
 * Use-case: создать/обновить производителя из строки внешнего файлового импорта
 * (mfa_id, name, provider). В отличие от UpsertManufacturerFromRowService (консольный
 * TecDoc-каскад, provider всегда TD), здесь provider обязателен и берётся из файла как есть —
 * ManufacturerSheetRowMapper бракует строку раньше, чем сюда попадёт пустое значение.
 */
final readonly class UpsertManufacturerFromSheetService implements UpsertManufacturerFromSheetServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-manufacturer-import';

    /**
     * Инициализирует порты сценария upsert производителя из внешнего sheet row.
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
     * Создает или обновляет производителя из внешнего import-листа.
     *
     * Шаги:
     * 1) Собрать raw row array из typed sheet DTO, включая provider из файла.
     * 2) Валидировать и преобразовать строку в `ManufacturerData`.
     * 3) Найти существующего производителя по `mfa_id`.
     * 4) Выполнить create или update через command.
     * 5) Опубликовать catalog mutation event о создании или обновлении.
     * 6) Вернуть сохраненный `ManufacturerData`.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(ManufacturerSheetRowDTO $row): ManufacturerData
    {
        $data = $this->factory->make([
            'mfa_id' => $row->mfaId,
            'name' => $row->name,
            'provider' => $row->provider,
        ]);

        $existing = $this->manufacturers->findByMfaId($data->mfaId);
        $manufacturer = $existing === null
            ? $this->command->create($data)
            : $this->command->updateByMfaId($data);

        event($existing === null
            ? new ManufacturerCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $manufacturer->toArray())
            : new ManufacturerUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $manufacturer->toArray()));

        return $manufacturer;
    }
}
