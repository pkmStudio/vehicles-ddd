<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\Applicability;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmTypeTemplateResolver;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseNomenclatureForApplicabilityDTO;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseTypeForApplicabilityDTO;
use Illuminate\Support\Facades\DB;
use stdClass;

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
        $query = DB::table('kits')->where('is_active', true);

        if ($kitId !== null) {
            $query->where('id', $kitId);
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
        return DB::table('kits')->where('id', $kitId)->exists();
    }

    /**
     * Преобразует SQL-строку kit в публичный DTO применяемости.
     * Шаги:
     * 1) Привести scalar поля комплекта к типам DTO.
     * 2) Загрузить номенклатуры состава комплекта в порядке сортировку.
     * 3) Загрузить type DTO комплекта по type_id.
     * 4) Собрать WarehouseKitForApplicabilityDTO без передачи Eloquent наружу.
     */
    private function mapKit(stdClass $kit): WarehouseKitForApplicabilityDTO
    {
        return new WarehouseKitForApplicabilityDTO(
            id: (int) $kit->id,
            typeId: (int) $kit->type_id,
            quantityInPackage: (int) $kit->quantity_in_package,
            isActive: (bool) $kit->is_active,
            nomenclatures: $this->nomenclatures((int) $kit->id),
            type: $this->type((int) $kit->type_id),
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
    private function nomenclatures(int $kitId): array
    {
        return DB::table('nomenclatures')
            ->join('kit_nomenclature', 'kit_nomenclature.nomenclature_id', '=', 'nomenclatures.id')
            ->where('kit_nomenclature.kit_id', $kitId)
            ->orderBy('kit_nomenclature.sort')
            ->select([
                'nomenclatures.id',
                'nomenclatures.type_id',
                'nomenclatures.quantity_in_pak',
                'nomenclatures.details',
                'kit_nomenclature.sort',
            ])
            ->get()
            ->map(fn (stdClass $nomenclature): WarehouseNomenclatureForApplicabilityDTO => new WarehouseNomenclatureForApplicabilityDTO(
                id: (int) $nomenclature->id,
                typeId: (int) $nomenclature->type_id,
                quantityInPak: (int) $nomenclature->quantity_in_pak,
                details: $this->jsonArray($nomenclature->details),
                sort: (int) $nomenclature->sort,
                type: $this->type((int) $nomenclature->type_id),
            ))
            ->all();
    }

    /**
     * Читает Warehouse type и добавляет template для применяемости.
     * Шаги:
     * 1) Найти type row по id в таблице types.
     * 2) Вернуть null, если type отсутствует.
     * 3) Привести id/name/char к scalar DTO fields.
     * 4) Определить detail template через локальный resolver.
     */
    private function type(int $typeId): ?WarehouseTypeForApplicabilityDTO
    {
        $type = DB::table('types')->where('id', $typeId)->first(['id', 'name', 'char']);

        if ($type === null) {
            return null;
        }

        return new WarehouseTypeForApplicabilityDTO(
            id: (int) $type->id,
            name: (string) $type->name,
            char: $type->char === null ? null : (string) $type->char,
            template: $this->templateResolver->resolve($type),
        );
    }

    /**
     * Нормализует jsonb details из DB row в array.
     * Шаги:
     * 1) Вернуть value как есть, если DB driver уже дал array.
     * 2) Для null/пустой/non-string вернуть пустой массив.
     * 3) Декодировать JSON строку в associative array.
     * 4) Вернуть пустой массив, если JSON не дал array.
     *
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
