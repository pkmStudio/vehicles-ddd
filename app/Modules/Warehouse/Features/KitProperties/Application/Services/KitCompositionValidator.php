<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionValidatorInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Проверяет бизнес-совместимость состава Warehouse-набора до расчёта производных свойств.
 */
final readonly class KitCompositionValidator implements KitCompositionValidatorInterface
{
    public function __construct(
        private TypeTemplateResolverInterface $templateResolver,
        private LoggerInterface $logger,
    ) {}

    /**
     * Валидирует состав набора.
     *
     * Шаги:
     * 1) Запретить пустой состав и смешанные type_id, кроме пары WIPER + WIPER_ADAPTER.
     * 2) Проверить единый бренд среди всех не-адаптеров; адаптеры не ограничивают бренд.
     * 3) Для щёток потребовать заполненную единую ручную категорию; адаптеры игнорируются.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    public function validate(Collection $nomenclatures): void
    {
        if ($nomenclatures->isEmpty()) {
            $this->fail($nomenclatures, 'Список номенклатур не может быть пустым');
        }

        $this->validateTypes($nomenclatures);
        $this->validateBrands($nomenclatures);
        $this->validateWiperCategories($nomenclatures);
    }

    /**
     * Проверяет, что состав не смешивает разные типы, кроме щётки с адаптером.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function validateTypes(Collection $nomenclatures): void
    {
        $typeIds = $nomenclatures->pluck('typeId')->unique()->values();
        if ($typeIds->count() <= 1) {
            return;
        }

        $templates = $this->templates($nomenclatures);
        $allowedWiperWithAdapter = $templates->count() === 2
            && $templates->contains(NomenclatureDetailTemplateEnum::WIPER)
            && $templates->contains(NomenclatureDetailTemplateEnum::WIPER_ADAPTER);

        if (! $allowedWiperWithAdapter) {
            $this->fail($nomenclatures, 'Нельзя собрать комплект из разных типов товаров. Исключение: щетка + адаптер.');
        }
    }

    /**
     * Проверяет, что не-адаптерные номенклатуры принадлежат одному бренду.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function validateBrands(Collection $nomenclatures): void
    {
        $brands = $nomenclatures
            ->reject(fn (NomenclatureData $nomenclature): bool => $this->isAdapter($nomenclature))
            ->pluck('brandId')
            ->filter(fn (?int $brandId): bool => $brandId !== null)
            ->unique()
            ->values();

        if ($brands->count() <= 1) {
            return;
        }

        $this->fail(
            $nomenclatures,
            'Нельзя собрать комплект из разных брендов: '.$brands->implode(', ').'.',
        );
    }

    /**
     * Проверяет, что все щётки имеют одну заполненную ручную категорию.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function validateWiperCategories(Collection $nomenclatures): void
    {
        $wipers = $nomenclatures
            ->filter(fn (NomenclatureData $nomenclature): bool => $this->isWiper($nomenclature))
            ->values();

        if ($wipers->isEmpty()) {
            return;
        }

        foreach ($wipers as $wiper) {
            $category = $this->categoryKey($wiper);
            if ($category === null) {
                $this->fail($nomenclatures, "У щетки {$wiper->partNumber} не заполнена категория.");
            }
        }

        $categories = $wipers
            ->map(fn (NomenclatureData $nomenclature): ?string => $this->categoryKey($nomenclature))
            ->filter()
            ->unique()
            ->values();

        if ($categories->count() <= 1) {
            return;
        }

        $labels = $categories
            ->map(fn (string $category): string => $this->categoryLabel($category))
            ->implode(', ');

        $this->fail(
            $nomenclatures,
            'Нельзя собрать комплект из разных категорий щеток: '.$labels.'.',
        );
    }

    private function isAdapter(NomenclatureData $nomenclature): bool
    {
        return $this->template($nomenclature) === NomenclatureDetailTemplateEnum::WIPER_ADAPTER;
    }

    private function isWiper(NomenclatureData $nomenclature): bool
    {
        return $this->template($nomenclature) === NomenclatureDetailTemplateEnum::WIPER;
    }

    private function template(NomenclatureData $nomenclature): ?NomenclatureDetailTemplateEnum
    {
        return $nomenclature->type === null ? null : $this->templateResolver->resolve($nomenclature->type);
    }

    /**
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     * @return Collection<int, NomenclatureDetailTemplateEnum>
     */
    private function templates(Collection $nomenclatures): Collection
    {
        return $nomenclatures
            ->map(fn (NomenclatureData $nomenclature): ?NomenclatureDetailTemplateEnum => $this->template($nomenclature))
            ->filter()
            ->unique()
            ->values();
    }

    private function categoryKey(NomenclatureData $nomenclature): ?string
    {
        $category = $nomenclature->details['category'] ?? null;
        if ($category === null) {
            return null;
        }

        $category = trim((string) $category);

        return $category === '' ? null : $category;
    }

    private function categoryLabel(string $category): string
    {
        return CategoryEnum::fromName($category)?->value ?? $category;
    }

    /**
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    private function fail(Collection $nomenclatures, string $message): never
    {
        $this->logger->warning(
            'KitPropertiesService: недопустимый состав комплекта',
            [
                'message' => $message,
                'type_ids' => $nomenclatures->pluck('typeId')->unique()->values()->all(),
                'brand_ids' => $nomenclatures->pluck('brandId')->filter()->unique()->values()->all(),
                'category_keys' => $nomenclatures
                    ->map(fn (NomenclatureData $nomenclature): ?string => $this->categoryKey($nomenclature))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'part_numbers' => $nomenclatures->pluck('partNumber')->values()->all(),
            ],
        );

        throw new InvalidArgumentException($message);
    }
}
