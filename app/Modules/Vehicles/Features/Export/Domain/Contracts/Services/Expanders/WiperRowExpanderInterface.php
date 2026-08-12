<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Порт разворота автомобилей со спецификациями дворников в export rows.
 */
interface WiperRowExpanderInterface
{
    /**
     * Возвращает строки, объединяющие front/back спецификации дворников для legacy export shape.
     *
     * Шаги:
     * 1) Обойти vehicles, подготовленные repository для wiper листа.
     * 2) Сгруппировать specifications по стороне дворника.
     * 3) Вернуть export row DTO с front/back specification pairs.
     *
     * @param  Collection<int, VehicleData>  $vehicles
     * @return Collection<int, WiperExportRowDTO>
     */
    public function expand(Collection $vehicles): Collection;
}
