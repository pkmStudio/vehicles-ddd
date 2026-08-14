<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\DTOs\EngineWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Events\EngineEventPayloadDTO;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineCreated;
use App\Modules\Vehicles\Shared\Domain\Events\Engine\EngineUpdated;
use App\Modules\Vehicles\Shared\Domain\Exceptions\ProviderOwnershipException;
use App\Modules\Vehicles\Shared\Domain\Services\Policy\EngineWritePolicy;

/**
 * Use-case: создать или обновить двигатель из нормализованной строки импорта.
 */
final readonly class UpsertEngineFromRowService implements UpsertEngineFromRowServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    /**
     * Инициализирует порты сценария upsert двигателя.
     *
     * Шаги:
     * 1) Сохранить command записи двигателя.
     * 2) Сохранить factory валидации и сборки `EngineData`.
     * 3) Сохранить repository для проверки существующей записи и генерации отрицательного id.
     */
    public function __construct(
        private EngineCommandInterface $command,
        private EngineDataFactoryInterface $factory,
        private EngineRepositoryInterface $engines,
        private EngineWritePolicy $writePolicy,
    ) {}

    /**
     * Создает или обновляет двигатель из import row DTO.
     *
     * Шаги:
     * 1) Разрешить eng_id для sheet DTO: использовать переданный или сгенерировать отрицательный.
     * 2) Собрать EngineData через factory-метод конкретного row DTO.
     * 3) Найти существующий двигатель по eng_id.
     * 4) Выполнить create/update через command.
     * 5) Опубликовать catalog mutation event с operation id из DTO.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(EngineSheetRowDTO|EngineTdRowDTO $row): EngineData
    {
        $row = $this->resolveEngId($row);
        $data = $row instanceof EngineTdRowDTO
            ? $this->factory->makeFromTdRow($row)
            : $this->factory->makeFromSheetRow($row);
        $existing = $this->engines->findByEngId($data->engId);
        try {
            $writeResult = $this->writePolicy->apply(
                incoming: EngineWritePolicyResultDTO::fromArray($data->toArray()),
                existing: $existing === null ? null : EngineWritePolicyResultDTO::fromArray($existing->toArray()),
                sourceProvider: $data->provider,
            );
        } catch (ProviderOwnershipException $e) {
            throw ImportRowValidationException::fromMessages($e->errors());
        }
        $writeData = EngineData::from($writeResult->toArray());

        $engine = $existing === null
            ? $this->command->create($writeData)
            : $this->command->update($writeData);

        $payload = new EngineEventPayloadDTO(
            id: (int) $engine->id,
            engId: $engine->engId,
            provider: $engine->provider,
            codeEngine: $engine->codeEngine,
            powerKwStart: $engine->powerKwStart,
            powerPsStart: $engine->powerPsStart,
            fuelType: $engine->fuelType,
            powerKwUpto: $engine->powerKwUpto,
            powerPsUpto: $engine->powerPsUpto,
            engineCapacity: $engine->engineCapacity,
            cylinderDiameter: $engine->cylinderDiameter,
            cylinderCount: $engine->cylinderCount,
            numberOfValves: $engine->numberOfValves,
            groupId: $engine->groupId,
            allowChangeFields: $engine->allowChangeFields,
        );

        event($existing === null
            ? new EngineCreated(self::IMPORT_USER_ID, $row->operationId, $payload)
            : new EngineUpdated(self::IMPORT_USER_ID, $row->operationId, $payload));

        return $engine;
    }

    /**
     * Возвращает row DTO с обязательным eng_id.
     *
     * Шаги:
     * 1) Если eng_id уже задан — вернуть DTO без изменений.
     * 2) Если DTO запрещает generated id — вернуть DTO без изменений, чтобы factory отдала validation error.
     * 3) Иначе взять минимальный eng_id и назначить следующий отрицательный.
     */
    private function resolveEngId(EngineSheetRowDTO|EngineTdRowDTO $row): EngineSheetRowDTO|EngineTdRowDTO
    {
        if ($row instanceof EngineTdRowDTO) {
            return $row;
        }

        if ($row->engId !== null || ! $row->generateNegativeEngIdWhenMissing) {
            return $row;
        }

        $minEngId = min($this->engines->findMinEngId()?->engId ?? 0, 0);

        return $row->withEngId(--$minEngId);
    }
}
