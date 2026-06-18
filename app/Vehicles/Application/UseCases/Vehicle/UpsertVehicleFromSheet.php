<?php

declare(strict_types=1);

namespace App\Vehicles\Application\UseCases\Vehicle;

use App\Vehicles\Application\DTOs\Vehicle\VehicleData;
use App\Vehicles\Application\Validators\Vehicle\VehicleValidator;
use App\Vehicles\Domain\Enums\SteeringTypeEnum;
use App\Vehicles\Domain\Models\Manufacturer;
use App\Vehicles\Domain\Models\Vehicle;
use App\Vehicles\Infrastructure\Commands\Vehicle\VehicleCommandInterface;

/**
 * Use-case: создать/обновить ТС из строки ручного листа.
 * Оркестрация: резолв производителя → валидация → запись через Command.
 * (Раньше — трейт HasVehicleImportBaseData со скрытым контрактом на хост.)
 */
final readonly class UpsertVehicleFromSheet
{
    public function __construct(
        private VehicleCommandInterface $command,
        private VehicleValidator $validator,
    ) {}

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function fromRow(array $row): Vehicle
    {
        $minMfaId = $this->getMinMfaId();
        $minMsId = $this->getMinMsId();

        $parentId = isset($row[13])
            ? Vehicle::query()->where('ms_id', $row[13])->first()?->id
            : null;

        [$mfaId, $manufacturerId] = $this->resolveManufacturer($minMfaId, $row);
        $msId = $row[2] ?? --$minMsId;

        $valid = $this->validator->validate([
            'ms_id' => $msId,
            'mfa_id' => $mfaId,
            'name' => $row[4] ?? null,
            'type' => $row[11] ?? null,
            'type_carcase' => ($row[10] ?? null) ?: null,
            'steering_type' => ($row[14] ?? null) ?: null,
            'generation' => $row[7] ?? null,
            'generation_short' => $row[6] ?? null,
            'localized_name' => $row[5] ?? null,
            'excel_table_id' => $row[0] ?? null,
            'provider' => $row[12] ?? null,
            'generation_year_from' => $row[8] ?? null,
            'generation_year_to' => $row[9] ?? null,
            'is_allow' => ($row[15] ?? null) === 'Да',
        ]);

        return $this->command->upsertByMsId(new VehicleData(
            msId: (int) $valid['ms_id'],
            mfaId: (int) $valid['mfa_id'],
            manufacturerId: $manufacturerId,
            name: (string) $valid['name'],
            type: (string) $valid['type'],
            steeringType: $valid['steering_type'] ?? SteeringTypeEnum::LEFT->value,
            generation: $valid['generation'] ?? null,
            typeCarcase: $valid['type_carcase'] ?? null,
            generationYearFrom: isset($valid['generation_year_from']) ? (int) $valid['generation_year_from'] : null,
            generationYearTo: isset($valid['generation_year_to']) ? (int) $valid['generation_year_to'] : null,
            provider: $valid['provider'] ?? 'TD',
            parentId: $parentId,
            excelTableId: $valid['excel_table_id'] ?? null,
            localizedName: $valid['localized_name'] ?? null,
            generationShort: $valid['generation_short'] ?? null,
            isAllow: (bool) ($valid['is_allow'] ?? false),
        ));
    }

    private function getMinMsId(): int
    {
        return min((int) Vehicle::query()->min('ms_id'), 0);
    }

    private function getMinMfaId(): int
    {
        return min((int) Manufacturer::query()->min('mfa_id'), 0);
    }

    private function resolveManufacturer(int &$minMfaId, array $row): array
    {
        if (empty($row[1])) {
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['name' => $row[3]],
                ['mfa_id' => --$minMfaId],
            );
        } else {
            $manufacturer = Manufacturer::query()->firstOrCreate(
                ['mfa_id' => $row[1]],
                ['name' => $row[3]],
            );
        }

        return [$manufacturer->mfa_id, $manufacturer->id];
    }
}
