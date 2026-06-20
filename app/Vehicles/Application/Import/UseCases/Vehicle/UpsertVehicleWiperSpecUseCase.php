<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Vehicle;

use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\PartSpecification;
use App\Vehicles\Domain\Models\Vehicle;

/**
 * Use-case: создать/обновить спецификацию «дворники» для ТС.
 * Сборка details из строки — забота адаптера (парсинг по шаблону); здесь — резолв
 * значения признака по имени через Repository и запись спецификации через Command.
 */
final readonly class UpsertVehicleWiperSpecUseCase
{
    public function __construct(
        private FeatureValueRepositoryInterface $featureValues,
        private PartSpecificationCommandInterface $partSpecs,
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации
     */
    public function execute(
        int $vehicleId,
        string $templateSlug,
        array $details,
        ?string $featureValueName = null,
        ?string $name = null,
        ?string $text = null,
    ): PartSpecification {
        $featureValueId = ! empty($featureValueName)
            ? $this->featureValues->firstByName($featureValueName)?->id
            : null;

        return $this->partSpecs->upsert(new PartSpecificationData(
            partableType: Vehicle::class,
            partableId: $vehicleId,
            template: DetailTemplateEnum::from($templateSlug),
            details: $details,
            featureValueId: $featureValueId,
            name: $name,
            text: $text,
        ));
    }
}
