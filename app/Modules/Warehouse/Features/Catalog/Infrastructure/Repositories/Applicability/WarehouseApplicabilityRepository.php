<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\Applicability;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseNomenclatureForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseTypeForApplicabilityDTO;

final readonly class WarehouseApplicabilityRepository implements WarehouseApplicabilityRepositoryInterface
{
    /**
     * Получает resolver Warehouse type template для DTO применяемости.
     * Шаги:
     * 1) Сохранить resolver, который переводит row таблицы types в template enum.
     * 2) Использовать его при сборке WarehouseTypeForApplicabilityDTO.
     */
    public function __construct(
        private NomenclatureCrmTypeTemplateResolver $templateResolver,
    ) {}

    /**
     * Читает активные комплекты Warehouse для расчета применяемости.
     * Шаги:
     * 1) Построить query по active kits.
     * 2) Если передан kitId, ограничить выборку одним комплектом.
     * 3) Читать строки lazyById chunk-ами, чтобы не держать весь каталог в памяти.
     * 4) Для каждой строки собрать WarehouseKitForApplicabilityDTO через mapKit().
     *
     * @return итерируемый набор<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        $query = Kit::query()
            ->with(['type', 'nomenclatures.type'])
            ->where('is_active', true);

        if ($kitId !== null) {
            $query->whereKey($kitId);
        }

        foreach ($query->lazyById($chunk) as $kit) {
            yield $this->mapKit($kit);
        }
    }

    /**
     * Проверяет существование комплекта Warehouse по id.
     * Шаги:
     * 1) Выполнить exists query по таблице kits.
     * 2) Вернуть логический флаг без загрузки полной строки комплекта.
     */
    public function kitExists(int $kitId): bool
    {
        return Kit::query()->whereKey($kitId)->exists();
    }

    /**
     * Преобразует SQL-строку kit в публичный DTO применяемости.
     * Шаги:
     * 1) Привести scalar поля комплекта к типам DTO.
     * 2) Загрузить номенклатуры состава комплекта в порядке сортировку.
     * 3) Загрузить type DTO комплекта по type_id.
     * 4) Собрать WarehouseKitForApplicabilityDTO без передачи Eloquent наружу.
     */
    private function mapKit(Kit $kit): WarehouseKitForApplicabilityDTO
    {
        return new WarehouseKitForApplicabilityDTO(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $this->nomenclatures($kit),
            type: $this->type($kit->type),
        );
    }

    /**
     * Загружает состав комплекта для расчета применяемости.
     * Шаги:
     * 1) Соединить nomenclatures с pivot kit_nomenclature по kit id.
     * 2) Отсортировать позиции по pivot сортировку.
     * 3) Выбрать только поля, нужные расчету применяемости.
     * 4) Для каждой позиции decoded details и type DTO добавить в WarehouseNomenclatureForApplicabilityDTO.
     *
     * @return array<int, WarehouseNomenclatureForApplicabilityDTO>
     */
    private function nomenclatures(Kit $kit): array
    {
        return $kit->nomenclatures
            ->map(fn (Nomenclature $nomenclature): WarehouseNomenclatureForApplicabilityDTO => new WarehouseNomenclatureForApplicabilityDTO(
                id: (int) $nomenclature->id,
                typeId: (int) $nomenclature->type_id,
                quantityInPak: (int) $nomenclature->quantity_in_pak,
                details: $nomenclature->details,
                sort: (int) $nomenclature->pivot->sort,
                type: $this->type($nomenclature->type),
            ))
            ->all();
    }

    /**
     * Читает Warehouse type и добавляет template для применяемости.
     * Шаги:
     * 1) Найти type row по id в таблице types.
     * 2) Привести id/name/char к scalar DTO fields.
     * 3) Определить detail template через локальный resolver.
     */
    private function type(Type $type): WarehouseTypeForApplicabilityDTO
    {
        return new WarehouseTypeForApplicabilityDTO(
            id: (int) $type->id,
            name: (string) $type->name,
            char: (string) $type->char,
            template: $this->templateResolver->resolve($type),
        );
    }
}
