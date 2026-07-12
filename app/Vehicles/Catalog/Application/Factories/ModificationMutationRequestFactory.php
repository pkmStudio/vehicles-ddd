<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Factories;

use App\Vehicles\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\DTOs\Modification\CreateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\DeleteModificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\ModificationMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Modification\UpdateModificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class ModificationMutationRequestFactory implements ModificationMutationRequestFactoryInterface
{
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
