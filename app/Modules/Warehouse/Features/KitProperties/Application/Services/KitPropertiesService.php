<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services;

use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients\PackagingClientInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionValidatorInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\KitPropertiesDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

/**
 * Считает производные свойства Warehouse-набора (Kit) по его составу. Packaging вызывается через
 * локальный клиент KitProperties, поэтому приложение не зависит от доменных типов соседней фичи.
 */
final readonly class KitPropertiesService implements KitPropertiesServiceInterface
{
    /**
     * @param  KitCompositionStrategyInterface[]  $strategies  упорядоченный список — первый подходящий побеждает
     */
    public function __construct(
        private PackagingClientInterface $packaging,
        private KitComplectationServiceInterface $complectationService,
        private KitCompositionValidatorInterface $compositionValidator,
        private LoggerInterface $logger,
        private array $strategies,
    ) {}

    /**
     * Этот метод собирает DTO свойств набора на основе коллекции номенклатур.
     *
     * Шаги:
     * 1) Определить подходящую стратегию состава по типам номенклатур.
     * 2) Резолвить итоговый тип набора и отфильтровать вспомогательные (не primary) номенклатуры.
     * 3) Подобрать/создать упаковку через Packaging (гасим только `PackDimensionNotResolvableException`).
     * 4) Посчитать quantity/вес/комплектацию/хэш состава.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO
    {
        $collection = new Collection($nomenclatures);
        $this->compositionValidator->validate($collection);

        $strategy = $this->resolveStrategy($collection);
        $type = $strategy->resolveType($collection);
        $primary = $strategy->primaryNomenclatures($collection);

        $packDimension = $this->resolvePackDimension($primary, $type);
        [$quantityInPackage, $quantityPackage] = $this->resolveQuantities($primary);
        $weight = $this->resolveWeight($collection, $packDimension);
        $complectation = $this->resolveComplectation($primary, max($quantityInPackage, $quantityPackage), $type);
        $importHash = self::compositionHash($collection->pluck('partNumber')->all());

        return new KitPropertiesDTO(
            typeId: $type->id ?? throw new UnexpectedValueException('TypeData::$id обязателен для расчёта свойств набора'),
            packDimensionId: $packDimension?->id,
            weight: $weight,
            quantityInPackage: $quantityInPackage,
            quantityPackage: $quantityPackage,
            complectation: $complectation,
            importHash: $importHash,
        );
    }

    /**
     * Этот метод определяет подходящую стратегию формирования состава по типам номенклатур.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function resolveStrategy(Collection $nomenclatures): KitCompositionStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            $strategySupportsNomenclatures = $strategy->supports($nomenclatures);

            if ($strategySupportsNomenclatures) {
                return $strategy;
            }
        }

        throw new UnexpectedValueException('Недопустимая комбинация типов номенклатур в наборе');
    }

    /**
     * Этот метод подбирает упаковку через Packaging, переводя свои Data-объекты в Packaging-шные.
     *
     * @param  Collection<int, NomenclatureData>  $primary
     */
    private function resolvePackDimension(Collection $primary, TypeData $type): ?PackDimensionDTO
    {
        try {
            return $this->packaging->selectOrCreate(
                type: $type,
                nomenclatures: $primary->all(),
            );
        } catch (PackDimensionNotResolvableException $e) {
            $this->logger->warning("Не удалось рассчитать упаковку для типа {$type->name}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Суммирует количество штук и упаковок по primary-номенклатурам состава.
     *
     * @param  Collection<int, NomenclatureData>  $primary
     * @return array{0: int, 1: int} [quantityInPackage, quantityPackage]
     */
    private function resolveQuantities(Collection $primary): array
    {
        return [(int) $primary->sum('quantityInPak'), (int) $primary->sum('quantityPak')];
    }

    /**
     * Складывает вес номенклатур и подобранной упаковки.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function resolveWeight(Collection $nomenclatures, ?PackDimensionDTO $packDimension): float
    {
        $toWeight = fn (NomenclatureData $nomenclature): int => $nomenclature->weight;
        $itemsWeight = $nomenclatures->sum($toWeight);

        return $itemsWeight + ($packDimension?->weight ?? 0);
    }

    /**
     * Формирует текст комплектации по matching primary-номенклатуре и материалу.
     *
     * Шаги:
     * 1) Найти primary-номенклатуру, чей type_id совпадает с итоговым типом набора.
     * 2) Взять первый непустой material из primary-состава.
     * 3) Нормализовать quantity и делегировать текст в KitComplectationService.
     *
     * @param  Collection<int, NomenclatureData>  $primary
     */
    private function resolveComplectation(Collection $primary, int $quantity, TypeData $type): string
    {
        $matchesType = fn (NomenclatureData $nomenclature): bool => $nomenclature->typeId === $type->id;

        /** @var NomenclatureData|null $matching */
        $matching = $primary->first($matchesType);
        $typeName = $matching?->type?->name;

        if ($typeName === null) {
            $this->logger->warning(
                'KitPropertiesService: не удалось сформировать комплектацию',
                [
                    'part_numbers' => $primary->pluck('partNumber')->all(),
                ],
            );

            return '';
        }

        $toMaterial = fn (NomenclatureData $nomenclature): array => $nomenclature->material;
        $isFilledMaterial = fn (array $material): bool => $material !== [];
        $materials = $primary->map($toMaterial);
        $material = $materials->first($isFilledMaterial) ?? [];

        if ($quantity <= 0) {
            $this->logger->warning(
                'KitPropertiesService: quantity <= 0, forcing 1',
                [
                    'part_numbers' => $primary->pluck('partNumber')->all(),
                ],
            );

            $quantity = 1;
        }

        return $this->complectationService->describe($quantity, $typeName, $material);
    }

    /**
     * @param  array<int, string>  $partNumbers
     */
    private static function compositionHash(array $partNumbers): string
    {
        sort($partNumbers);

        return md5(implode('|', $partNumbers));
    }

}
