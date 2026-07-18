<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
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
    /**
     * Получает resolver detail-шаблона типа номенклатуры.
     */
    public function __construct(
        private TypeTemplateResolverInterface $templateResolver,
    ) {}

    /**
     * Проверяет, что набор состоит из щёток и адаптеров.
     */
    public function supports(Collection $nomenclatures): bool
    {
        $templates = $this->distinctTemplates($nomenclatures);

        return count($templates) === 2
            && in_array(NomenclatureDetailTemplateEnum::WIPER, $templates, true)
            && in_array(NomenclatureDetailTemplateEnum::WIPER_ADAPTER, $templates, true);
    }

    /**
     * Возвращает тип щётки как итоговый тип набора щётка+адаптер.
     */
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

    /**
     * Возвращает основные номенклатуры, исключая адаптеры из расчёта упаковки/количества.
     */
    public function primaryNomenclatures(Collection $nomenclatures): Collection
    {
        return $nomenclatures
            ->filter(fn (NomenclatureData $n): bool => $this->template($n) !== NomenclatureDetailTemplateEnum::WIPER_ADAPTER)
            ->values();
    }

    /**
     * Резолвит detail-шаблон конкретной номенклатуры.
     */
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
