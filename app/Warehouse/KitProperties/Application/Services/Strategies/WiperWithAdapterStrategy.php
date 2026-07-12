<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Application\Services\Strategies;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Комплект щётка + адаптер. Адаптер — вспомогательный тип, не влияет на упаковку/количество.
 * В отличие от dan-center (сравнение хардкод `TypeEnum::WIPER->value`/`WIPER_ADAPTER->value`),
 * здесь `type_id` — динамический id из БД, поэтому WIPER/WIPER_ADAPTER различаются по резолвнутому
 * detail-шаблону (`TypeTemplateResolverInterface`), не по конкретному числу.
 */
final readonly class WiperWithAdapterStrategy implements KitCompositionStrategyInterface
{
    public function __construct(
        private TypeTemplateResolverInterface $templateResolver,
    ) {}

    public function supports(Collection $nomenclatures): bool
    {
        $templates = $this->distinctTemplates($nomenclatures);

        return count($templates) === 2
            && in_array(NomenclatureDetailTemplateEnum::WIPER, $templates, true)
            && in_array(NomenclatureDetailTemplateEnum::WIPER_ADAPTER, $templates, true);
    }

    public function resolveType(Collection $nomenclatures): TypeData
    {
        /** @var NomenclatureData|null $wiper */
        $wiper = $nomenclatures->first(fn (NomenclatureData $n): bool => $this->template($n) === NomenclatureDetailTemplateEnum::WIPER);

        if ($wiper === null || $wiper->type === null) {
            throw new UnexpectedValueException(
                'WiperWithAdapterStrategy: не найдена номенклатура типа WIPER с загруженным type',
            );
        }

        return $wiper->type;
    }

    public function primaryNomenclatures(Collection $nomenclatures): Collection
    {
        return $nomenclatures
            ->filter(fn (NomenclatureData $n): bool => $this->template($n) !== NomenclatureDetailTemplateEnum::WIPER_ADAPTER)
            ->values();
    }

    private function template(NomenclatureData $nomenclature): ?NomenclatureDetailTemplateEnum
    {
        return $nomenclature->type === null ? null : $this->templateResolver->resolve($nomenclature->type);
    }

    /**
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     * @return array<int, NomenclatureDetailTemplateEnum>
     */
    private function distinctTemplates(Collection $nomenclatures): array
    {
        return $nomenclatures
            ->map(fn (NomenclatureData $n): ?NomenclatureDetailTemplateEnum => $this->template($n))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
