<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperVehicleSideDetailsDTO;

interface TemplatesClientInterface
{
    /**
     * Определяет сторону vehicle wiper details через Templates shared-kernel.
     *
     * Шаги:
     * 1. Принимает raw `details` спецификации автомобиля.
     * 2. Передает данные в Templates boundary для распознавания стороны.
     * 3. Возвращает найденное значение стороны или `null`.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * Возвращает типизированный снимок details для выбранной стороны дворников.
     *
     * Шаги:
     * 1. Принимает raw `details` и сторону, которую нужно извлечь.
     * 2. Делегирует разбор Templates boundary.
     * 3. Возвращает локальный DTO стороны для calculation-сценария.
     *
     * @param  array<string, mixed>  $details
     */
    public function vehicleWiperSideData(array $details, string $side): WiperVehicleSideDetailsDTO;
}
