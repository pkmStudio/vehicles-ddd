<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Vehicle;

use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Vehicle;
use App\Vehicles\Domain\Services\WiperSpecificationService;

/**
 * Use-case: записать спецификацию «дворники» для ТС.
 * Дворники хранятся по ОДНОЙ записи на сторону (front/back): собранные из строки details
 * разбиваются по сторонам (доменный сервис), и каждая сторона upsert-ится отдельно —
 * существующая запись стороны ищется по `template + side` (jsonb), затем update либо create.
 */
final readonly class UpsertVehicleWiperSpecUseCase implements \App\Vehicles\Domain\Contracts\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCaseInterface
{
    public function __construct(
        private FeatureValueRepositoryInterface $featureValues,
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationCommandInterface $command,
        private WiperSpecificationService $wiper,
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации (front/back)
     */
    public function execute(
        int $vehicleId,
        string $templateSlug,
        array $details,
        ?string $featureValueName = null,
        ?string $name = null,
        ?string $text = null,
    ): void {
        $template = DetailTemplateEnum::from($templateSlug);
        $featureValueId = ! empty($featureValueName)
            ? $this->featureValues->firstByName($featureValueName)?->id
            : null;

        foreach ($this->wiper->splitDetails($details) as $part) {
            $data = new PartSpecificationData(
                partableType: Vehicle::class,
                partableId: $vehicleId,
                template: $template,
                details: $part['details'],
                featureValueId: $featureValueId,
                name: $name,
                text: $text,
            );

            $existing = $this->specifications->firstByVehicleTemplateAndSide($vehicleId, $template, $part['side']);

            $existing
                ? $this->command->update($existing, $data)
                : $this->command->create($data);
        }
    }
}
