<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

/**
 * Входящая ссылка на существующий двигатель, связанный с модификацией.
 */
final readonly class ModificationEngineRequestDTO
{
    /**
     * Хранит внешний id существующего двигателя из сообщения мутации модификации.
     */
    public function __construct(
        public int $engId,
    ) {}
}
