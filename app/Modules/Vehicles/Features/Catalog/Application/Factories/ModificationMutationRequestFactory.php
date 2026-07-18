<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Собирает DTO запроса мутации модификаций из валидированного payload.
 */
final readonly class ModificationMutationRequestFactory implements ModificationMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации модификаций из payload.
     *
     * Шаги:
     * 1) Прочитать тип операции из payload.
     * 2) Собрать конкретный DTO запроса операции.
     * 3) Вернуть общий DTO мутации с операцией и request.
     */
    public function make(array $payload): ModificationMutationRequestDTO
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new ModificationMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => $this->createRequest($payload),
                CatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                CatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * Собирает DTO конкретной операции модификаций из общего DTO или payload.
     */
    private function createRequest(array $payload): CreateModificationRequestDTO
    {
        $modification = $payload['modification'];

        return new CreateModificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            modId: (int) $modification['mod_id'],
            msId: (int) $modification['ms_id'],
            type: VehicleTypeEnum::from((string) $modification['type']),
            yearFrom: isset($modification['year_from']) ? (int) $modification['year_from'] : null,
            yearTo: isset($modification['year_to']) ? (int) $modification['year_to'] : null,
            description: isset($modification['description']) ? (string) $modification['description'] : null,
            powerPs: isset($modification['power_ps']) ? (int) $modification['power_ps'] : null,
            powerKw: isset($modification['power_kw']) ? (int) $modification['power_kw'] : null,
            engineType: isset($modification['engine_type']) ? EngineTypeEnum::from((string) $modification['engine_type']) : null,
            gearType: isset($modification['gear_type']) ? GearTypeEnum::from((string) $modification['gear_type']) : null,
            driveType: isset($modification['drive_type']) ? DriveTypeEnum::from((string) $modification['drive_type']) : null,
            brakeSystemType: isset($modification['brake_system_type']) ? BrakeSystemTypeEnum::from((string) $modification['brake_system_type']) : null,
            numberOfCylinders: isset($modification['number_of_cylinders']) ? (int) $modification['number_of_cylinders'] : null,
            capacityLt: isset($modification['capacity_lt']) ? (float) $modification['capacity_lt'] : null,
        );
    }

    /**
     * Собирает DTO конкретной операции модификаций из общего DTO или payload.
     */
    private function updateRequest(array $payload): UpdateModificationRequestDTO
    {
        $modification = $payload['modification'];

        return new UpdateModificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            modId: (int) $modification['mod_id'],
            msId: (int) $modification['ms_id'],
            type: VehicleTypeEnum::from((string) $modification['type']),
            yearFrom: isset($modification['year_from']) ? (int) $modification['year_from'] : null,
            yearTo: isset($modification['year_to']) ? (int) $modification['year_to'] : null,
            description: isset($modification['description']) ? (string) $modification['description'] : null,
            powerPs: isset($modification['power_ps']) ? (int) $modification['power_ps'] : null,
            powerKw: isset($modification['power_kw']) ? (int) $modification['power_kw'] : null,
            engineType: isset($modification['engine_type']) ? EngineTypeEnum::from((string) $modification['engine_type']) : null,
            gearType: isset($modification['gear_type']) ? GearTypeEnum::from((string) $modification['gear_type']) : null,
            driveType: isset($modification['drive_type']) ? DriveTypeEnum::from((string) $modification['drive_type']) : null,
            brakeSystemType: isset($modification['brake_system_type']) ? BrakeSystemTypeEnum::from((string) $modification['brake_system_type']) : null,
            numberOfCylinders: isset($modification['number_of_cylinders']) ? (int) $modification['number_of_cylinders'] : null,
            capacityLt: isset($modification['capacity_lt']) ? (float) $modification['capacity_lt'] : null,
        );
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(array $payload): DeleteModificationRequestDTO
    {
        return new DeleteModificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            modId: (int) $payload['modification']['mod_id'],
            type: VehicleTypeEnum::from((string) $payload['modification']['type']),
        );
    }
}
