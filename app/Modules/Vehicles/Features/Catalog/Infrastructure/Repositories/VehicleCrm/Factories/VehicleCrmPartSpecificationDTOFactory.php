<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;

final readonly class VehicleCrmPartSpecificationDTOFactory
{
    public function make(object $specification): VehicleCrmPartSpecificationDTO
    {
        return new VehicleCrmPartSpecificationDTO(
            id: (int) $specification->id,
            partableType: (string) $specification->partable_type,
            partableId: (int) $specification->partable_id,
            featureId: isset($specification->feature_id) ? (int) $specification->feature_id : null,
            featureName: isset($specification->feature_name) ? (string) $specification->feature_name : null,
            featureValueId: isset($specification->feature_value_id) ? (int) $specification->feature_value_id : null,
            featureValueName: isset($specification->feature_value_name) ? (string) $specification->feature_value_name : null,
            featureValueShortCode: isset($specification->feature_value_short_code) ? (string) $specification->feature_value_short_code : null,
            template: (string) $specification->template,
            name: isset($specification->name) ? (string) $specification->name : null,
            text: isset($specification->text) ? (string) $specification->text : null,
            details: $this->details($specification->details),
            createdAt: isset($specification->created_at) ? (string) $specification->created_at : null,
            updatedAt: isset($specification->updated_at) ? (string) $specification->updated_at : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function details(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
