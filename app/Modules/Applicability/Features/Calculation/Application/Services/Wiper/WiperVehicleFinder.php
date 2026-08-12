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
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Находит vehicle part specifications, совместимые с рассчитанными параметрами дворников.
 */
final readonly class WiperVehicleFinder implements WiperVehicleFinderInterface
{
    /**
     * Получает read clients и logger для поиска vehicle specifications.
     *
     * Шаги:
     * 1. Сохраняет Vehicles client для выборки specifications по длинам дворников.
     * 2. Сохраняет Templates client для чтения стороны и adapters из vehicle details.
     * 3. Сохраняет logger для anomalies, когда найденная specification имеет неожиданную сторону.
     */
    public function __construct(
        private VehiclesApplicabilityClientInterface $vehicles,
        private TemplatesClientInterface $templates,
        private LoggerInterface $logger,
    ) {}

    /**
     * Возвращает совместимые спецификации по позиции комплекта дворников.
     *
     * Шаги:
     * 1. Для front-комплекта ищет только передние specifications.
     * 2. Для back-комплекта ищет только задние specifications.
     * 3. Для universal-комплекта объединяет front и rear результаты.
     * 4. Удаляет дубликаты specifications по id.
     */
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

    /**
     * Ищет передние vehicle specifications по длинам и фильтрует их по adapters.
     *
     * Шаги:
     * 1. Запрашивает front specifications у Vehicles client по расчетным длинам.
     * 2. Фильтрует candidates по front side adapters.
     *
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function front(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters): Collection
    {
        return $this->filterByAdapters(
            specifications: $this->vehicles->frontWiperSpecifications($wipers),
            adapters: $adapters,
            side: WiperSideEnum::FRONT,
        );
    }

    /**
     * Ищет задние vehicle specifications по длинам и фильтрует их по adapters.
     *
     * Шаги:
     * 1. Запрашивает rear specifications у Vehicles client по расчетным длинам.
     * 2. Фильтрует candidates по back side adapters.
     *
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function rear(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters): Collection
    {
        return $this->filterByAdapters(
            specifications: $this->vehicles->rearWiperSpecifications($wipers),
            adapters: $adapters,
            side: WiperSideEnum::BACK,
        );
    }

    /**
     * Отбрасывает specifications с неподходящей стороной или adapters.
     *
     * Шаги:
     * 1. Для каждой specification определяет сохраненную сторону через Templates client.
     * 2. При несовпадении стороны пишет warning и исключает specification из результата.
     * 3. Читает typed side details для ожидаемой стороны.
     * 4. Проверяет adapters vehicle side против adapters комплекта.
     * 5. Возвращает переиндексированную коллекцию совместимых specifications.
     *
     * @param  Collection<int, VehiclePartSpecificationData>  $specifications
     * @return Collection<int, VehiclePartSpecificationData>
     */
    private function filterByAdapters(Collection $specifications, WiperAdaptersDTO $adapters, WiperSideEnum $side): Collection
    {
        return $specifications
            ->filter(function (VehiclePartSpecificationData $specification) use ($adapters, $side): bool {
                $detectedSide = $this->templates->detectVehicleWiperSide($specification->details);
                if ($detectedSide !== $side->value) {
                    $this->logger->warning('Wiper applicability skipped specification with unexpected side', [
                        'part_specification_id' => $specification->id,
                        'expected_side' => $side->value,
                        'detected_side' => $detectedSide,
                    ]);

                    return false;
                }

                $sideData = $this->templates->vehicleWiperSideData($specification->details, $side->value);

                return $this->checkAdapters($sideData->adapters($side), $adapters);
            })
            ->values();
    }

    /**
     * Проверяет, что adapters автомобиля покрываются adapters комплекта.
     *
     * Шаги:
     * 1. Нормализует adapters автомобиля, все adapters комплекта и put adapters.
     * 2. Если у автомобиля adapters пустые, а комплект adapters требует, возвращает `false`.
     * 3. Проверяет, что каждый adapter автомобиля входит в adapters комплекта.
     * 4. Если есть put adapters, требует пересечение vehicle adapters с put adapters.
     *
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

    /**
     * Приводит значения adapters к уникальному списку строк.
     *
     * Шаги:
     * 1. Cast-ит каждое значение к string.
     * 2. Убирает дубликаты с сохранением порядка первого появления.
     *
     * @param  array<int, mixed>  $values
     */
    private function uniqueStrings(array $values): array
    {
        $values = array_map(static fn (mixed $value): string => (string) $value, $values);

        return array_values(array_unique($values));
    }
}
