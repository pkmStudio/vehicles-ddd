<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

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
     * Шаги:
     * 1) Сохранить TypeTemplateResolverInterface для определения WIPER/WIPER_ADAPTER по warehouse type.
     */
    public function __construct(
        private TypeTemplateResolverInterface $templateResolver,
    ) {}

    /**
     * Проверяет, что набор состоит из щёток и адаптеров.
     * Шаги:
     * 1) Собрать уникальные detail templates состава.
     * 2) Проверить, что templates ровно два.
     * 3) Подтвердить наличие WIPER и WIPER_ADAPTER.
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
     * Шаги:
     * 1) Найти первую номенклатуру, чей template равен WIPER.
     * 2) Проверить, что у найденной щётки загружен type.
     * 3) Вернуть type щётки как итоговый type набора.
     * 4) Если щётка или type не найдены, выбросить KitCompositionException.
     */
    public function resolveType(Collection $nomenclatures): TypeData
    {
        $isWiper = fn (NomenclatureData $nomenclature): bool => $this->template($nomenclature) === NomenclatureDetailTemplateEnum::WIPER;

        /** @var NomenclatureData|null $wiper */
        $wiper = $nomenclatures->first($isWiper);

        if ($wiper === null || $wiper->type === null) {
            throw new KitCompositionException(
                'WiperWithAdapterStrategy: не найдена номенклатура типа WIPER с загруженным type',
            );
        }

        return $wiper->type;
    }

    /**
     * Возвращает основные номенклатуры, исключая адаптеры из расчёта упаковки/количества.
     * Шаги:
     * 1) Для каждой номенклатуры определить detail template.
     * 2) Исключить позиции с template WIPER_ADAPTER.
     * 3) Вернуть переиндексированную коллекцию primary-номенклатур.
     */
    public function primaryNomenclatures(Collection $nomenclatures): Collection
    {
        $isPrimaryNomenclature = fn (NomenclatureData $nomenclature): bool => $this->template($nomenclature) !== NomenclatureDetailTemplateEnum::WIPER_ADAPTER;

        return $nomenclatures
            ->filter($isPrimaryNomenclature)
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
        $toTemplate = fn (NomenclatureData $nomenclature): ?NomenclatureDetailTemplateEnum => $this->template($nomenclature);

        return $nomenclatures
            ->map($toTemplate)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
