<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Compact projection автомобиля для CRM autocomplete.
 */
final readonly class VehicleCrmSearchItemDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит поля option-элемента поиска автомобиля.
     */
    public function __construct(
        public int $id,
        public string $label,
        public int $msId,
        public ?string $manufacturer = null,
    ) {}

    /**
     * Возвращает публичный option payload поиска CRM.
     *
     * @return array{id: int, label: string, ms_id: int, manufacturer: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'ms_id' => $this->msId,
            'manufacturer' => $this->manufacturer,
        ];
    }
}
