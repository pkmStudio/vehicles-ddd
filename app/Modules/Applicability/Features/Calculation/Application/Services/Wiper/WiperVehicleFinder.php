<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperVehicleFinderInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class WiperVehicleFinder implements WiperVehicleFinderInterface
{
    public function __construct(
        private VehiclesApplicabilityClientInterface $vehicles,
        private TemplatesClientInterface $templates,
    ) {}

    public function find(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters, WiperKitPositionEnum $position): Collection
    {
        return match ($position) {
            WiperKitPositionEnum::FRONT => $this->front($wipers, $adapters),
            WiperKitPositionEnum::BACK => $this->rear($wipers, $adapters),
            WiperKitPositionEnum::UNIVERSAL => $this->front($wipers, $adapters)
                ->merge($this->rear($wipers, $adapters))
                ->unique('id')
                ->values(),
        };
    }

    /** @return Collection<int, VehiclePartSpecificationData> */
    private function front(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters): Collection
    {
        return $this->filterByAdapters(
            specifications: $this->vehicles->frontWiperSpecifications($wipers),
            adapters: $adapters,
            side: 'front',
        );
    }

    /** @return Collection<int, VehiclePartSpecificationData> */
    private function rear(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters): Collection
    {
        return $this->filterByAdapters(
            specifications: $this->vehicles->rearWiperSpecifications($wipers),
            adapters: $adapters,
            side: 'back',
        );
    }

    /**
     * @param  Collection<int, VehiclePartSpecificationData>  $specifications
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function filterByAdapters(Collection $specifications, WiperAdaptersDTO $adapters, string $side): Collection
    {
        return $specifications
            ->filter(function (VehiclePartSpecificationData $specification) use ($adapters, $side): bool {
                $detectedSide = $this->templates->detectVehicleWiperSide($specification->details);
                if ($detectedSide !== $side) {
                    Log::warning('Wiper applicability skipped specification with unexpected side', [
                        'part_specification_id' => $specification->id,
                        'expected_side' => $side,
                        'detected_side' => $detectedSide,
                    ]);

                    return false;
                }

                $sideData = $this->templates->vehicleWiperSideData($specification->details, $side);
                $field = $side === 'front' ? 'adapter_type_front' : 'adapter_type_rear';

                return $this->checkAdapters($this->adapterList($sideData[$field] ?? []), $adapters);
            })
            ->values();
    }

    /**
     * @param  array<int, string>  $vehicleAdapters
     */
    public function checkAdapters(array $vehicleAdapters, WiperAdaptersDTO $adapters): bool
    {
        $vehicleAdapters = $this->uniqueStrings($vehicleAdapters);
        $requiredAdapters = $this->uniqueStrings($adapters->allAdapters);
        $requiredPutAdapters = $this->uniqueStrings($adapters->putAdapters);

        if ($vehicleAdapters === [] && $requiredAdapters !== []) {
            return false;
        }

        $result = array_diff($vehicleAdapters, $requiredAdapters) === [];

        if ($requiredPutAdapters !== [] && $result) {
            $result = array_intersect($vehicleAdapters, $requiredPutAdapters) !== [];
        }

        return $result;
    }

    /** @return array<int, string> */
    private function adapterList(mixed $value): array
    {
        $value = is_array($value) ? $value : [$value];
        $value = array_map(static fn (mixed $adapter): string => trim((string) $adapter), $value);

        return array_values(array_filter($value, static fn (string $adapter): bool => $adapter !== ''));
    }

    /** @param array<int, mixed> $values */
    private function uniqueStrings(array $values): array
    {
        $values = array_map(static fn (mixed $value): string => (string) $value, $values);

        return array_values(array_unique($values));
    }
}
