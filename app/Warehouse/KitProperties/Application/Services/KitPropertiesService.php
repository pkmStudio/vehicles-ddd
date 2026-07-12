<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Application\Services;

use App\Warehouse\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\DTOs\KitPropertiesDTO;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\Exceptions\PackDimensionNotResolvableException;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData as PackagingNomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData as PackagingPackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData as PackagingTypeData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Считает производные свойства Warehouse-набора (Kit) по его составу. Зовёт `Packaging` через порт
 * `PackagingServiceInterface` — межфичевый вызов внутри домена, не shared kernel, поэтому на
 * границе явно переводит свои `TypeData`/`NomenclatureData` в Packaging-шные (см. план, п.1): обе
 * фичи держат собственные копии этих Data-классов, единая структура полей — совпадение, не связь.
 */
final readonly class KitPropertiesService implements KitPropertiesServiceInterface
{
    /**
     * @param  KitCompositionStrategyInterface[]  $strategies  упорядоченный список — первый подходящий побеждает
     */
    public function __construct(
        private PackagingServiceInterface $packaging,
        private KitComplectationServiceInterface $complectationService,
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
        if ($nomenclatures === []) {
            throw new InvalidArgumentException('Список номенклатур не может быть пустым');
        }

        $collection = new Collection($nomenclatures);

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
            if ($strategy->supports($nomenclatures)) {
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
    private function resolvePackDimension(Collection $primary, TypeData $type): ?PackagingPackDimensionData
    {
        try {
            return $this->packaging->selectOrCreate(
                type: $this->toPackagingType($type),
                nomenclatures: $primary
                    ->map(fn (NomenclatureData $n): PackagingNomenclatureData => $this->toPackagingNomenclature($n))
                    ->all(),
            );
        } catch (PackDimensionNotResolvableException $e) {
            Log::warning(
                message: "Не удалось рассчитать упаковку для типа {$type->name}: {$e->getMessage()}",
            );

            return null;
        }
    }

    /**
     * @param  Collection<int, NomenclatureData>  $primary
     * @return array{0: int, 1: int} [quantityInPackage, quantityPackage]
     */
    private function resolveQuantities(Collection $primary): array
    {
        return [(int) $primary->sum('quantityInPak'), (int) $primary->sum('quantityPak')];
    }

    /**
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function resolveWeight(Collection $nomenclatures, ?PackagingPackDimensionData $packDimension): float
    {
        $itemsWeight = $nomenclatures->sum(fn (NomenclatureData $n): int => $n->weight);

        return $itemsWeight + ($packDimension?->weight ?? 0);
    }

    /**
     * @param  Collection<int, NomenclatureData>  $primary
     */
    private function resolveComplectation(Collection $primary, int $quantity, TypeData $type): string
    {
        /** @var NomenclatureData|null $matching */
        $matching = $primary->first(fn (NomenclatureData $n): bool => $n->typeId === $type->id);
        $typeName = $matching?->type?->name;

        if ($typeName === null) {
            Log::warning('KitPropertiesService: не удалось сформировать комплектацию', [
                'part_numbers' => $primary->pluck('partNumber')->all(),
            ]);

            return '';
        }

        $material = $primary->map(fn (NomenclatureData $n): array => $n->material)->first(fn (array $m): bool => $m !== []) ?? [];

        if ($quantity <= 0) {
            Log::warning('KitPropertiesService: quantity <= 0, forcing 1', [
                'part_numbers' => $primary->pluck('partNumber')->all(),
            ]);

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

    /**
     * Переводит TypeData фичи KitProperties в TypeData фичи Packaging.
     */
    private function toPackagingType(TypeData $type): PackagingTypeData
    {
        return new PackagingTypeData(
            name: $type->name,
            char: $type->char,
            id: $type->id,
        );
    }

    /**
     * Переводит NomenclatureData фичи KitProperties в NomenclatureData фичи Packaging.
     */
    private function toPackagingNomenclature(NomenclatureData $nomenclature): PackagingNomenclatureData
    {
        return new PackagingNomenclatureData(
            partNumber: $nomenclature->partNumber,
            quantityInPak: $nomenclature->quantityInPak,
            details: $nomenclature->details,
            id: $nomenclature->id,
        );
    }
}
