<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper;

final readonly class WiperLengthDTO
{
    public function __construct(
        public int $lengthMain,
        public ?int $lengthSecond,
        public int $countWipers,
    ) {}
}
