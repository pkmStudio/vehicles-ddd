<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper;

use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;

final readonly class WiperVehicleSideDetailsDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private array $data,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array<int, string>
     */
    public function adapters(WiperSideEnum $side): array
    {
        $value = $this->data[$side->adapterField()] ?? [];
        $value = array_map(static fn (string $adapter): string => trim($adapter), $value);

        return array_values(array_filter($value, static fn (string $adapter): bool => $adapter !== ''));
    }
}
