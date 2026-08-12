<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;

/**
 * Anti-corruption client к Templates public API для import details Vehicles.
 */
final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Получить публичный Templates client.
     *
     * Шаги:
     * 1) Принять public Templates contract через DI.
     * 2) Сохранить его для делегирования операций import details.
     */
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    /**
     * Собрать vehicle details из Excel row через Templates shared-kernel.
     *
     * Шаги:
     * 1) Преобразовать enum шаблона в публичное строковое значение.
     * 2) Передать row и start index в Templates client.
     * 3) Вернуть normalized details payload.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    public function buildVehicleDetails(DetailTemplateEnum $template, array $row, int $startIndex): array
    {
        return $this->templates->buildVehicleDetails($template->value, $row, $startIndex);
    }

    /**
     * Разделить объединенные wiper details на side-specific payloads.
     *
     * Шаги:
     * 1) Передать normalized details в Templates client.
     * 2) Вернуть список details payload по сторонам дворника.
     *
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperDetails(array $details): array
    {
        return $this->templates->splitVehicleWiperDetails($details);
    }

    /**
     * Определить сторону дворника по details payload.
     *
     * Шаги:
     * 1) Передать details в Templates client.
     * 2) Вернуть side code или null, если сторона не определена.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->templates->detectVehicleWiperSide($details);
    }

    /**
     * Получить normalized данные одной стороны дворника.
     *
     * Шаги:
     * 1) Передать details и side code в Templates client.
     * 2) Вернуть side-specific payload.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->templates->vehicleWiperSideData($details, $side);
    }
}
