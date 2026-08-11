<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehiclesApplicabilityRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability\VehicleApplicabilityLookupDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * SQL adapter read API Vehicles Catalog для Applicability.
 */
final readonly class VehiclesApplicabilityRepository implements VehiclesApplicabilityRepositoryInterface
{
    /**
     * Читает передние спецификации дворников, подходящие под параметры комплекта.
     *
     * Шаги:
     * 1. Собирает базовый query для front-only wiper specifications.
     * 2. Добавляет фильтры по основной длине и количеству щеток.
     * 3. Добавляет фильтр по второй длине, если она передана.
     * 4. Маппит найденные строки в DTO для Applicability.
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection
    {
        $query = $this->baseWiperQuery(WiperSideEnum::FRONT)
            ->where('details->front->length_main->min', '<=', $lengthMain)
            ->where('details->front->length_main->max', '>=', $lengthMain)
            ->where('details->front->count_wipers', $countWipers);

        if ($lengthSecond !== null) {
            $query
                ->where('details->front->length_second->min', '<=', $lengthSecond)
                ->where('details->front->length_second->max', '>=', $lengthSecond);
        }

        return $this->mapSpecifications($query->get());
    }

    /**
     * Читает задние спецификации дворников, подходящие под параметры комплекта.
     *
     * Шаги:
     * 1. Собирает базовый query для back-only wiper specifications.
     * 2. Добавляет фильтры по задней длине и количеству щеток.
     * 3. Выполняет query.
     * 4. Маппит найденные строки в DTO для Applicability.
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection
    {
        return $this->mapSpecifications(
            $this->baseWiperQuery(WiperSideEnum::BACK)
                ->where('details->back->length_rear->min', '<=', $lengthMain)
                ->where('details->back->length_rear->max', '>=', $lengthMain)
                ->where('details->back->count_wipers', $countWipers)
                ->get(),
        );
    }

    /**
     * Читает lookup автомобиля по внешнему `ms_id`.
     *
     * Шаги:
     * 1. Ищет строку Vehicles по `ms_id`.
     * 2. Возвращает `null`, если автомобиль не найден.
     * 3. Маппит id, `ms_id` и `parent_id` в lookup DTO.
     */
    public function findVehicleByMsId(int $msId): ?VehicleApplicabilityLookupDTO
    {
        $vehicle = DB::table('vehicles')
            ->where('ms_id', $msId)
            ->first(['id', 'ms_id', 'parent_id']);

        if ($vehicle === null) {
            return null;
        }

        return new VehicleApplicabilityLookupDTO(
            id: (int) $vehicle->id,
            msId: (int) $vehicle->ms_id,
            parentId: $vehicle->parent_id === null ? null : (int) $vehicle->parent_id,
        );
    }

    /**
     * Читает внешний `ms_id` автомобиля по внутреннему id.
     *
     * Шаги:
     * 1. Ищет значение `ms_id` в таблице Vehicles по primary key.
     * 2. Возвращает `null`, если значение отсутствует.
     * 3. Нормализует найденное значение в integer.
     */
    public function findVehicleMsIdById(int $id): ?int
    {
        $msId = DB::table('vehicles')
            ->where('id', $id)
            ->value('ms_id');

        return $msId === null ? null : (int) $msId;
    }

    /**
     * Читает id модификации по внешним `ms_id` и `mod_id`.
     *
     * Шаги:
     * 1. Фильтрует Modifications по внешнему `ms_id` автомобиля.
     * 2. Добавляет фильтр по внешнему `mod_id` модификации.
     * 3. Возвращает внутренний id модификации или `null`.
     */
    public function findModificationIdByMsAndModId(int $msId, int $modId): ?int
    {
        $id = DB::table('modifications')
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Собирает базовый query для single-side wiper specifications.
     *
     * Шаги:
     * 1. Определяет противоположную сторону дворников.
     * 2. Ограничивает выборку vehicle part specifications с шаблоном `wiper`.
     * 3. Требует наличие выбранной стороны и отсутствие противоположной стороны в JSONB details.
     */
    private function baseWiperQuery(WiperSideEnum $side): Builder
    {
        $otherSide = $side === WiperSideEnum::FRONT ? WiperSideEnum::BACK : WiperSideEnum::FRONT;

        return DB::table('part_specifications')
            ->where('partable_type', PartableTypeEnum::VEHICLE->value)
            ->where('template', DetailTemplateEnum::WIPER->value)
            ->whereRaw("jsonb_exists(details, '{$side->value}')")
            ->whereRaw("NOT jsonb_exists(details, '{$otherSide->value}')");
    }

    /**
     * Маппит SQL rows спецификаций в DTO для Applicability.
     *
     * Шаги:
     * 1. Проходит по collection строк query result.
     * 2. Приводит ids к integer и details к массиву.
     * 3. Возвращает переиндексированную collection DTO.
     *
     * @param  Collection<int, stdClass>  $specifications
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    private function mapSpecifications(Collection $specifications): Collection
    {
        return $specifications
            ->map(fn (stdClass $specification): VehiclePartSpecificationForApplicabilityDTO => new VehiclePartSpecificationForApplicabilityDTO(
                id: (int) $specification->id,
                vehicleId: (int) $specification->partable_id,
                details: $this->jsonArray($specification->details),
            ))
            ->values();
    }

    /**
     * Нормализует JSONB details из SQL result в массив.
     *
     * Шаги:
     * 1. Возвращает value как есть, если это уже массив.
     * 2. Возвращает пустой массив для пустого или нестрокового значения.
     * 3. Декодирует JSON-строку и возвращает массив либо пустой fallback.
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
