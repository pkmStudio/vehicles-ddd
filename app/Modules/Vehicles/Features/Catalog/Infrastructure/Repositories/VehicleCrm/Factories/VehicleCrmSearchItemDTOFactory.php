<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;

/**
 * Маппит SQL projection автомобиля в CRM search item DTO.
 */
final readonly class VehicleCrmSearchItemDTOFactory
{
    /**
     * Создает search item DTO автомобиля.
     *
     * Шаги:
     * 1. Принимает SQL projection автомобиля.
     * 2. Собирает человекочитаемый label.
     * 3. Возвращает compact DTO для autocomplete.
     */
    public function make(object $vehicle): VehicleCrmSearchItemDTO
    {
        return new VehicleCrmSearchItemDTO(
            id: (int) $vehicle->id,
            label: $this->label($vehicle),
            msId: (int) $vehicle->ms_id,
            manufacturer: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
        );
    }

    /**
     * Собирает label автомобиля для CRM autocomplete.
     *
     * Шаги:
     * 1. Берет идентификатор, производителя, модель и поколение из projection.
     * 2. Добавляет localized name и годы выпуска.
     * 3. Возвращает форматированную строку label.
     */
    private function label(object $vehicle): string
    {
        return sprintf(
            '%s | %s %s %s | %s (%s-%s)',
            $vehicle->ms_id,
            $vehicle->manufacturer_name,
            $vehicle->name,
            $vehicle->generation,
            $vehicle->localized_name ?: '',
            $vehicle->generation_year_from,
            $vehicle->generation_year_to ?: 'н.в.',
        );
    }
}
