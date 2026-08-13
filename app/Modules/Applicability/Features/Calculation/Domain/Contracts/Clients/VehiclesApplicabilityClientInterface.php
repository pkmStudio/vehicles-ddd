<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use Illuminate\Support\Collection;

interface VehiclesApplicabilityClientInterface
{
    /**
     * Читает front-спецификации дворников автомобилей по расчетным длинам комплекта.
     *
     * Шаги:
     * 1. Передает длины основного и второго дворника во внешний Vehicles boundary.
     * 2. Ограничивает поиск количеством дворников из комплекта.
     * 3. Возвращает локальные `VehiclePartSpecificationData` для расчета применяемости.
     *
     * @return Collection<int, VehiclePartSpecificationData>
     */
    public function frontWiperSpecifications(WiperLengthDTO $length): Collection;

    /**
     * Читает rear-спецификации дворников автомобилей по расчетной длине комплекта.
     *
     * Шаги:
     * 1. Передает заднюю длину как основной размер во внешний Vehicles boundary.
     * 2. Ограничивает поиск количеством дворников из комплекта.
     * 3. Возвращает локальные `VehiclePartSpecificationData` для расчета применяемости.
     *
     * @return Collection<int, VehiclePartSpecificationData>
     */
    public function rearWiperSpecifications(WiperLengthDTO $length): Collection;
}
