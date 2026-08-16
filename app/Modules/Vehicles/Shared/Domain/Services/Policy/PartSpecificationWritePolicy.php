<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Services\Policy;

use App\Modules\Vehicles\Shared\Domain\DTOs\Policy\PartSpecificationWritePolicyResultDTO;
use App\Modules\Vehicles\Shared\Domain\Exceptions\PartSpecificationUniquenessException;

/**
 * Единое правило записи part specification для import и catalog mutation workflows.
 */
final readonly class PartSpecificationWritePolicy
{
    /**
     * Применяет правило уникальности и возвращает готовый снимок для записи.
     *
     * Шаги:
     * 1) Принять incoming snapshot и заранее найденный id дубля.
     * 2) Если дубль отсутствует — разрешить запись без изменения snapshot.
     * 3) Если дубль есть — выбросить domain exception с id дубля.
     */
    public function apply(
        PartSpecificationWritePolicyResultDTO $incoming,
        ?int $duplicateId,
    ): PartSpecificationWritePolicyResultDTO {
        if ($duplicateId === null) {
            return $incoming;
        }

        throw PartSpecificationUniquenessException::duplicate($duplicateId);
    }
}
