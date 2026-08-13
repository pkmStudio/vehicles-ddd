<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Application\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionValidatorInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Проверяет бизнес-совместимость состава Warehouse-набора до расчёта производных свойств.
 */
final readonly class KitCompositionValidator implements KitCompositionValidatorInterface
{
    /**
     * Получает зависимости для определения template номенклатуры и фиксации ошибок состава.
     * Шаги:
     * 1) Сохранить resolver, который переводит warehouse type в detail template.
     * 2) Сохранить logger для actionable warning перед доменным исключением.
     */
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
     * Шаги:
     * 1) Собрать уникальные type_id из состава комплекта.
     * 2) Если тип один или типы отсутствуют, считать правило типов выполненным.
     * 3) Разрешить ровно комбинацию шаблонов WIPER и WIPER_ADAPTER.
     * 4) Для любой другой смеси типов выбросить KitCompositionException через fail().
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
     * Шаги:
     * 1) Исключить adapter-позиции, потому что они могут быть отдельного бренда.
     * 2) Собрать уникальные заполненные brandId оставшихся позиций.
     * 3) Разрешить пустой или единственный бренд.
     * 4) Для нескольких брендов выбросить ошибку с перечислением brandId.
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
     * Шаги:
     * 1) Оставить только позиции с template WIPER.
     * 2) Если щёток нет, не применять правило категорий.
     * 3) Для каждой щётки потребовать непустой details.category.
     * 4) Собрать уникальные category keys по щёткам.
     * 5) Для нескольких категорий вывести человекочитаемые labels и выбросить ошибку состава.
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

    /**
     * Проверяет, относится ли номенклатура к template адаптера щётки.
     * Шаги:
     * 1) Определить template через type resolver.
     * 2) Сравнить template с WIPER_ADAPTER.
     */
    private function isAdapter(NomenclatureData $nomenclature): bool
    {
        return $this->template($nomenclature) === NomenclatureDetailTemplateEnum::WIPER_ADAPTER;
    }

    /**
     * Проверяет, относится ли номенклатура к template щётки.
     * Шаги:
     * 1) Определить template через type resolver.
     * 2) Сравнить template с WIPER.
     */
    private function isWiper(NomenclatureData $nomenclature): bool
    {
        return $this->template($nomenclature) === NomenclatureDetailTemplateEnum::WIPER;
    }

    /**
     * Определяет detail template номенклатуры по warehouse type.
     * Шаги:
     * 1) Если type отсутствует в snapshot номенклатуры, вернуть null.
     * 2) Иначе передать type в TypeTemplateResolverInterface.
     */
    private function template(NomenclatureData $nomenclature): ?NomenclatureDetailTemplateEnum
    {
        return $nomenclature->type === null ? null : $this->templateResolver->resolve($nomenclature->type);
    }

    /**
     * Собирает уникальные templates состава комплекта.
     * Шаги:
     * 1) Для каждой номенклатуры определить template по type.
     * 2) Отбросить null для неизвестных/пустых типов.
     * 3) Вернуть уникальный ordered список templates.
     *
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

    /**
     * Извлекает normalized category key из details щётки.
     * Шаги:
     * 1) Прочитать details.category, если ключ есть.
     * 2) Привести значение к trimmed string.
     * 3) Вернуть null для отсутствующей или пустой категории.
     */
    private function categoryKey(NomenclatureData $nomenclature): ?string
    {
        $category = $nomenclature->details['category'] ?? null;
        if ($category === null) {
            return null;
        }

        $category = trim((string) $category);

        return $category === '' ? null : $category;
    }

    /**
     * Переводит сохраненный category key в label для сообщения пользователю.
     * Шаги:
     * 1) Попробовать найти enum case по сохраненному name.
     * 2) Вернуть enum value как человекочитаемый label.
     * 3) Для неизвестного key вернуть исходную строку, чтобы не потерять диагностический контекст.
     */
    private function categoryLabel(string $category): string
    {
        return CategoryEnum::fromName($category)?->value ?? $category;
    }

    /**
     * Фиксирует недопустимый состав и выбрасывает доменное исключение.
     * Шаги:
     * 1) Собрать диагностический контекст: type_id, brand_id, category keys и артикулы.
     * 2) Записать warning, потому что это actionable business validation failure.
     * 3) Выбросить KitCompositionException с пользовательским сообщением.
     *
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

        throw new KitCompositionException($message);
    }
}
