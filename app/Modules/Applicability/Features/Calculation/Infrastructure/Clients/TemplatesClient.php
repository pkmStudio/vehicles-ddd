<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\TemplatesClientInterface as LocalTemplatesClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperVehicleSideDetailsDTO;
use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements LocalTemplatesClientInterface
{
    /**
     * Получает публичный Templates client для перевода details.
     *
     * Шаги:
     * 1. Сохраняет shared-kernel client Templates.
     * 2. Использует его как единственную точку доступа к разбору vehicle wiper details.
     */
    public function __construct(
        private TemplatesClientInterface $templates,
    ) {}

    /**
     * Определяет сторону vehicle wiper details через Templates.
     *
     * Шаги:
     * 1. Принимает raw details спецификации автомобиля.
     * 2. Делегирует распознавание публичному Templates client.
     * 3. Возвращает сторону или `null`, не раскрывая внешний контракт за пределы adapter-а.
     *
     * @param  array<string, mixed>  $details
     */
    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->templates->detectVehicleWiperSide($details);
    }

    /**
     * Переводит данные стороны дворников из Templates в локальный DTO.
     *
     * Шаги:
     * 1. Запрашивает raw side data у публичного Templates client.
     * 2. Собирает локальный `WiperVehicleSideDetailsDTO`.
     * 3. Возвращает DTO calculation-фиче.
     *
     * @param  array<string, mixed>  $details
     */
    public function vehicleWiperSideData(array $details, string $side): WiperVehicleSideDetailsDTO
    {
        return WiperVehicleSideDetailsDTO::fromArray($this->templates->vehicleWiperSideData($details, $side));
    }
}
