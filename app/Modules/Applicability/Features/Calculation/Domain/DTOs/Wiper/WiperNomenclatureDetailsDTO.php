<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\WiperSideEnum;

final readonly class WiperNomenclatureDetailsDTO
{
    /**
     * Создает accessor над raw details номенклатуры дворников.
     *
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
     * Возвращает позицию дворника из details.
     */
    public function position(): ?WiperKitPositionEnum
    {
        return WiperKitPositionEnum::fromStoredValue($this->data['position'] ?? null);
    }

    /**
     * Возвращает основную длину дворника из details.
     */
    public function lengthMain(): ?int
    {
        return $this->intValue('length_main');
    }

    /**
     * Возвращает вторую длину переднего комплекта из details.
     */
    public function lengthSecond(): ?int
    {
        return $this->intValue('length_second');
    }

    /**
     * Возвращает заднюю длину дворника из details.
     */
    public function lengthRear(): ?int
    {
        return $this->intValue('length_rear');
    }

    /**
     * @return array<int, string>
     */
    public function adapters(WiperSideEnum $side): array
    {
        return $this->adapterList($this->data[$side->adapterField()] ?? []);
    }

    /**
     * @return array<int, string>
     */
    public function adaptersByField(string $field): array
    {
        return $this->adapterList($this->data[$field] ?? []);
    }

    private function intValue(string $field): ?int
    {
        $value = $this->data[$field] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function adapterList(mixed $value): array
    {
        $value = is_array($value) ? $value : [];
        $value = array_map(static fn (mixed $adapter): string => trim((string) $adapter), $value);

        return array_values(array_filter($value, static fn (string $adapter): bool => $adapter !== ''));
    }
}
