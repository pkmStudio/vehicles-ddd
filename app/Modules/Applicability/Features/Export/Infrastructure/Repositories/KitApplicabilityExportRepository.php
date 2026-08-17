<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Repositories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Models\KitApplicability;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilitySourceEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class KitApplicabilityExportRepository implements KitApplicabilityExportRepositoryInterface
{
    /**
     * Читает применяемость комплектов к vehicle part specifications для Excel export.
     *
     * Шаги:
     * 1. Отбирает записи `kit_applicabilities` с target type `PART_SPECIFICATION`.
     * 2. Загружает комплект с номенклатурами и связанную vehicle specification.
     * 3. Обходит данные chunk-ами, чтобы не держать весь query result в памяти Eloquent.
     * 4. Пропускает неполные связи, которые нельзя корректно вывести в export.
     * 5. Собирает `VehicleKitApplicabilityRowDTO` с kit, vehicle и generation fields.
     */
    public function vehicleRows(): Collection
    {
        $rows = collect();

        KitApplicability::query()
            ->where('target_type', ApplicabilityTargetTypeEnum::PART_SPECIFICATION)
            ->with(['kit.nomenclatures', 'partSpecification.vehicle'])
            ->chunkById(1000, function (Collection $applicabilities) use ($rows): void {
                foreach ($applicabilities as $applicability) {
                    $kit = $applicability->kit;
                    $specification = $applicability->partSpecification;
                    $vehicle = $specification?->vehicle;

                    if ($kit === null || $specification === null || $vehicle === null) {
                        continue;
                    }

                    $rows->push(new VehicleKitApplicabilityRowDTO(
                        kitId: (int) $kit->id,
                        partNumbers: $kit->nomenclatures->pluck('part_number')->implode(';'),
                        excelTableId: $vehicle->excel_table_id,
                        vehicleMsId: (int) $vehicle->ms_id,
                        vehicleName: (string) $vehicle->name,
                        generation: $vehicle->generation,
                        yearFrom: (int) $vehicle->generation_year_from,
                        yearTo: $vehicle->generation_year_to === null ? null : (int) $vehicle->generation_year_to,
                        typeCarcase: $vehicle->type_carcase,
                    ));
                }
            });

        return $rows;
    }

    /**
     * Читает импортированную применяемость комплектов к модификациям для XLSX export.
     *
     * Шаги:
     * 1. Отбирает `kit_applicabilities` по target type `MODIFICATION`.
     * 2. Ограничивает выгрузку ручным XLSX-источником, который можно обратно импортировать.
     * 3. Подгружает modification и kit.type, пропускает битые ссылки.
     * 4. Возвращает строки в порядке `ms_id`, `mod_id`, `kit_id` с внутренним `typeChar`.
     */
    public function modificationRows(): Collection
    {
        $rows = collect();

        KitApplicability::query()
            ->where('target_type', ApplicabilityTargetTypeEnum::MODIFICATION)
            ->where('source', ApplicabilitySourceEnum::IMPORTED)
            ->where('algorithm', KitApplicabilityAlgorithmEnum::MANUAL_XLSX)
            ->with(['modification', 'kit.type'])
            ->chunkById(1000, function (Collection $applicabilities) use ($rows): void {
                foreach ($applicabilities as $applicability) {
                    $modification = $applicability->modification;
                    $kit = $applicability->kit;
                    $typeChar = $kit?->type?->char;

                    if ($modification === null || $kit === null || ! is_string($typeChar) || $typeChar === '') {
                        Log::warning('Applicability modification export skipped row without modification or kit type', [
                            'kit_id' => (int) $applicability->kit_id,
                            'ms_id' => $modification?->ms_id === null ? null : (int) $modification->ms_id,
                            'mod_id' => $modification?->mod_id === null ? null : (int) $modification->mod_id,
                        ]);

                        continue;
                    }

                    $rows->push(new ModificationKitApplicabilityRowDTO(
                        msId: (int) $modification->ms_id,
                        modId: (int) $modification->mod_id,
                        kitId: (int) $applicability->kit_id,
                        typeChar: $typeChar,
                    ));
                }
            });

        return $rows;
    }
}
