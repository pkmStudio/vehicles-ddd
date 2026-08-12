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
     * Шаги:
     * 1) Сохранить repository номенклатуры выбранного Warehouse-типа.
     * 2) Сохранить repository типов для проверки typeId и справочного листа.
     * 3) Сохранить builder базовых колонок номенклатуры.
     * 4) Сохранить resolver detail template по типу.
     * 5) Сохранить Templates client для заголовков, reference options и render details.
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
     * Шаги:
     * 1) Найти typeId через type(), чтобы неконсистентный запрос упал явно.
     * 2) Прочитать номенклатуры этого типа через repository port.
     * 3) Вернуть коллекцию typed NomenclatureData для Excel adapter-а.
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
     * Шаги:
     * 1) Найти Warehouse type и определить его detail template.
     * 2) Для типа без template оставить только базовые заголовки.
     * 3) Для типа с template получить detail headings из Templates boundary.
     * 4) Объединить базовые и detail-specific колонки в порядке Excel-листа.
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
     * Шаги:
     * 1) Определить detail template из type snapshot строки.
     * 2) Для строки без template оставить details-ячейки пустыми.
     * 3) Для строки с template отрендерить сохраненный details массив через Templates client.
     * 4) Объединить базовые Excel-ячейки с detail-specific ячейками.
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
     * Шаги:
     * 1) Найти TypeData по id через type().
     * 2) Вернуть name типа как title листа.
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
            $valueAtIndex = fn (array $values): mixed => $values[$i] ?? null;

            $rows[] = array_map(
                $valueAtIndex,
                $columns,
            );
        }

        return collect($rows);
    }

    /**
     * Возвращает заголовки справочного листа для выбранного типа номенклатуры.
     * Шаги:
     * 1) Найти Warehouse type и определить detail template.
     * 2) Начать с базовых reference columns: тип, материал, вид техники.
     * 3) Добавить имена template-specific справочников из Templates client.
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
     * Шаги:
     * 1) Запросить TypeData через TypeRepositoryInterface.
     * 2) Вернуть найденный type snapshot.
     * 3) Если typeId неизвестен, выбросить InvalidArgumentException с id запроса.
     */
    private function type(int $typeId): TypeData
    {
        return $this->types->findById($typeId)
            ?? throw new InvalidArgumentException("Тип Warehouse #{$typeId} не найден");
    }

    /**
     * Резолвит detail-шаблон для Warehouse-типа.
     * Шаги:
     * 1) Передать TypeData в TypeTemplateResolverInterface.
     * 2) Вернуть найденный NomenclatureDetailTemplateEnum или null.
     */
    private function template(TypeData $type): ?NomenclatureDetailTemplateEnum
    {
        return $this->templates->resolve($type);
    }

    /**
     * Возвращает справочники template-specific полей в порядке колонок Excel.
     * Шаги:
     * 1) Для null template вернуть пустой список справочников.
     * 2) Для конкретного template запросить reference options через Templates client.
     * 3) Вернуть map заголовок колонки => список допустимых значений.
     *
     * @return array<string, list<string>>
     */
    private function templateReferences(?NomenclatureDetailTemplateEnum $template): array
    {
        return $template === null ? [] : $this->templatesClient->nomenclatureReferenceOptions($template);
    }
}
