<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Infrastructure\Clients;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Exceptions\VehicleApplicabilityException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Публичный синхронный клиент Vehicles для расчета и импорта применяемости.
 */
final readonly class VehiclesApplicabilityClient implements VehiclesApplicabilityClientInterface
{
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

    public function resolveModificationIdByMsAndModId(int $msId, int $modId): int
    {
        $vehicle = DB::table('vehicles')
            ->where('ms_id', $msId)
            ->first(['id', 'ms_id', 'parent_id']);

        if ($vehicle === null) {
            throw new VehicleApplicabilityException("Модель (ms_id: {$msId}) не найдена.");
        }

        $modification = $this->findModification((int) $vehicle->ms_id, $modId);
        if ($modification !== null) {
            return (int) $modification->id;
        }

        $parentMsId = $vehicle->parent_id === null
            ? null
            : DB::table('vehicles')->where('id', (int) $vehicle->parent_id)->value('ms_id');

        if ($parentMsId !== null) {
            $modification = $this->findModification((int) $parentMsId, $modId);
            if ($modification !== null) {
                return (int) $modification->id;
            }

            throw new VehicleApplicabilityException(
                "Модификация (ms_id: {$vehicle->ms_id}, mod_id: {$modId}) не найдена ни у модели, ни у родителя (parent_ms_id: {$parentMsId}).",
            );
        }

        throw new VehicleApplicabilityException("Модификация (ms_id: {$vehicle->ms_id}, mod_id: {$modId}) не найдена.");
    }

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

    private function findModification(int $msId, int $modId): ?stdClass
    {
        return DB::table('modifications')
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->first(['id']);
    }

    /**
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
