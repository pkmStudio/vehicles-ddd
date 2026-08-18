<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

/**
 * Описывает один набор, который bulk-delete не смог обработать успешно.
 */
final readonly class KitBulkDeleteErrorDTO
{
    /**
     * Получает id записи, machine-readable причину и опциональный бизнес-ключ.
     */
    public function __construct(
        public ?int $id,
        public string $reason,
        public ?string $businessKey = null,
    ) {}

    /**
     * Преобразует ошибку в machine-readable payload результата.
     *
     * @return array{id?: int, reason: string, business_key?: string}
     */
    public function toArray(): array
    {
        $data = [
            'reason' => $this->reason,
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->businessKey !== null) {
            $data['business_key'] = $this->businessKey;
        }

        return $data;
    }
}
