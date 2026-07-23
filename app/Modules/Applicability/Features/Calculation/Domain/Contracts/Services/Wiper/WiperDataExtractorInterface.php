<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperDataExtractorInterface
{
    public function extractPosition(KitData $kit): WiperKitPositionEnum;

    public function extractLength(KitData $kit, ?WiperKitPositionEnum $position = null): WiperLengthDTO;

    public function extractAdapters(KitData $kit, ?WiperKitPositionEnum $position = null): WiperAdaptersDTO;
}
