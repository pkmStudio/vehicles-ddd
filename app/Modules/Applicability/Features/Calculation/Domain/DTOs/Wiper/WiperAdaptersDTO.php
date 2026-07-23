<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper;

/**
 * @param  array<int, string>  $allAdapters
 * @param  array<int, string>  $putAdapters
 */
final readonly class WiperAdaptersDTO
{
    public function __construct(
        public array $allAdapters,
        public array $putAdapters,
    ) {}
}
