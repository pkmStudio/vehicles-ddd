<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\Services;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\NomenclatureExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows\NomenclatureExportRowInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Координирует чтение номенклатуры, detail-шаблонов и справочников для Excel-экспорта.
 */
final readonly class NomenclatureExportService implements NomenclatureExportServiceInterface
{
    private const array MATERIAL_REFERENCE = [
        'Никель',
        'Платина',
        'Иридий',
        'Двойной иридий',
        'Двойная платина',
        'Двойной никель',
        'Сталь',
        'Металл',
        'ABS пластик',
        'Бумага',
        'Резина',
        'Пластик',
    ];

    private const array VEHICLE_TYPE_REFERENCE = [
        'Легковые автомобили',
        'Коммерческий транспорт',
        'Внедорожники',
        'Грузовые автомобили и автобусы',
    ];

    /**
     * Получает порты чтения, построения базовой строки и рендера detail-полей.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private TypeRepositoryInterface $types,
        private NomenclatureExportRowInterface $row,
        private TypeTemplateResolverInterface $templates,
        private TemplatesClientInterface $templatesClient,
    ) {}

    /**
     * Проверяет существование типа и возвращает строки номенклатуры этого типа.
     *
     * @return Collection<int, NomenclatureData>
     */
    public function getRows(int $typeId): Collection
    {
        $this->type($typeId);

        return $this->nomenclatures->forType($typeId);
    }

    /**
     * Возвращает базовые и template-specific заголовки листа номенклатуры.
     *
     * @return array<int, string>
     */
    public function getHeadings(int $typeId): array
    {
        $template = $this->template($this->type($typeId));
        $detailHeadings = $template === null ? [] : $this->templatesClient->nomenclatureDetailHeadings($template);

        return array_merge($this->row->getBaseHeadings(), $detailHeadings);
    }

    /**
     * Преобразует одну номенклатуру в строку Excel с учётом её detail-шаблона.
     *
     * @return array<int, mixed>
     */
    public function mapRow(NomenclatureData $row): array
    {
        $template = $row->type === null ? null : $this->templates->resolve($row->type);
        $details = $template === null
            ? []
            : $this->templatesClient->renderNomenclatureDetails($template, $row->details);

        return array_merge($this->row->getBaseData($row), $details);
    }

    /**
     * Возвращает имя типа для названия Excel-листа.
     */
    public function title(int $typeId): string
    {
        return $this->type($typeId)->name;
    }

    /**
     * Собирает строки справочного листа для выбранного типа номенклатуры.
     *
     * Шаги:
     * 1) Подготовить базовые справочники типов, материалов и видов техники.
     * 2) Добавить справочники detail-полей выбранного шаблона.
     * 3) Выровнять колонки по максимальной высоте и вернуть строки.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function getReferenceRows(int $typeId): Collection
    {
        $columns = [
            $this->types->all()->pluck('name')->values()->all(),
            self::MATERIAL_REFERENCE,
            self::VEHICLE_TYPE_REFERENCE,
        ];

        $template = $this->template($this->type($typeId));
        foreach ($this->templateReferences($template) as $values) {
            $columns[] = $values;
        }

        $max = max(array_map('count', $columns));
        $rows = [];

        for ($i = 0; $i < $max; $i++) {
            $rows[] = array_map(
                fn (array $values): mixed => $values[$i] ?? null,
                $columns,
            );
        }

        return collect($rows);
    }

    /**
     * Возвращает заголовки справочного листа для выбранного типа номенклатуры.
     *
     * @return array<int, string>
     */
    public function getReferenceHeadings(int $typeId): array
    {
        $template = $this->template($this->type($typeId));

        return array_merge(
            ['Тип товара', 'Материал', 'Вид техники / автотранспорта'],
            array_keys($this->templateReferences($template)),
        );
    }

    /**
     * Возвращает тип номенклатуры или падает на неконсистентном запросе экспорта.
     */
    private function type(int $typeId): TypeData
    {
        return $this->types->findById($typeId)
            ?? throw new InvalidArgumentException("Тип Warehouse #{$typeId} не найден");
    }

    /**
     * Резолвит detail-шаблон для Warehouse-типа.
     */
    private function template(TypeData $type): ?NomenclatureDetailTemplateEnum
    {
        return $this->templates->resolve($type);
    }

    /**
     * Возвращает справочники template-specific полей в порядке колонок Excel.
     *
     * @return array<string, list<string>>
     */
    private function templateReferences(?NomenclatureDetailTemplateEnum $template): array
    {
        return $template === null ? [] : $this->templatesClient->nomenclatureReferenceOptions($template);
    }
}
