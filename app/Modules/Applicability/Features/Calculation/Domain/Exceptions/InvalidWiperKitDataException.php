<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Exceptions;

use RuntimeException;

final class InvalidWiperKitDataException extends RuntimeException
{
    public static function missingWiperPosition(int $kitId): self
    {
        return new self("No wiper position found for kit {$kitId}");
    }

    public static function missingWiperBySort(int $kitId, int $sort): self
    {
        return new self("Wiper nomenclature with sort {$sort} not found for kit {$kitId}");
    }
}
