<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

interface TemplatesClientInterface
{
    /**
     * Собрать vehicle details выбранного шаблона из Excel row.
     *
     * Шаги:
     * 1) Передать template, row и стартовую позицию в Templates shared-kernel.
     * 2) Вернуть normalized details payload.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildVehicleDetails(DetailTemplateEnum $template, array $row, int $startIndex): array;

    /**
     * Разделить объединенные wiper details на side-specific records.
     *
     * Шаги:
     * 1) Передать normalized wiper details в Templates shared-kernel.
     * 2) Вернуть список details payload по сторонам.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperDetails(array $details): array;

    /**
     * Определить сторону дворника по details payload.
     *
     * Шаги:
     * 1) Передать details в Templates presenter.
     * 2) Вернуть side code или null.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * Получить данные конкретной стороны дворника.
     *
     * Шаги:
     * 1) Передать details и side в Templates presenter.
     * 2) Вернуть normalized side-specific payload.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;
}
