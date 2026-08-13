<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Порт разворота двигателей со спецификациями свечей в export rows.
 */
interface EngineSparkPlugSpecificationRowExpanderInterface
{
    /**
     * Возвращает по одной строке на двигатель без спецификаций или на каждую найденную specification.
     *
     * Шаги:
     * 1) Обойти engines, подготовленные repository для spark plug листа.
     * 2) Создать export row на каждую specification или пустую строку двигателя.
     *
     * @param  Collection<int, EngineData>  $entities
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function expand(Collection $entities): Collection;
}
