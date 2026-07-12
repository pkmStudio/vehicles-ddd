<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services;

use App\Warehouse\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use RuntimeException;

/**
 * Selector: по detail-шаблону типа выбирает стратегию подбора упаковки из зарегистрированных
 * портов стратегий.
 */
final readonly class PackagingService implements PackagingServiceInterface
{
    /**
     * Получает чтение упаковок, resolver шаблона и упорядоченный список стратегий.
     *
     * @param  array<int, PackagingStrategyInterface>  $strategies
     */
    public function __construct(
        private PackDimensionRepositoryInterface $repository,
        private TypeTemplateResolverInterface $templateResolver,
        private array $strategies,
    ) {}

    /**
     * Этот метод резолвит detail-шаблон типа, читает упаковки этого типа и делегирует расчёт
     * соответствующей стратегии.
     *
     * Шаги:
     * 1) Определить detail-шаблон типа номенклатуры.
     * 2) Загрузить доступные упаковки этого типа.
     * 3) Найти первую стратегию, поддерживающую шаблон, и делегировать ей расчёт.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionData
    {
        $template = $this->templateResolver->resolve($type);
        $packDimensions = $this->repository->byType($type);

        foreach ($this->strategies as $strategy) {
            if (! $strategy->supports($template)) {
                continue;
            }

            return $strategy->calculate($type, $nomenclatures, $packDimensions);
        }

        throw new RuntimeException('Не зарегистрирована стратегия подбора Warehouse-упаковки');
    }
}
