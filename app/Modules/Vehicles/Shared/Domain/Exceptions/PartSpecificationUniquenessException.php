<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Exceptions;

use DomainException;

/**
 * Ошибка правила уникальности part specification natural-key.
 */
final class PartSpecificationUniquenessException extends DomainException
{
    private function __construct(
        public readonly int $duplicateId,
    ) {
        parent::__construct("Спецификация с таким владельцем, шаблоном и details уже существует (ID={$duplicateId}).");
    }

    /**
     * Создает ошибку найденного дубля owner/template/details.
     */
    public static function duplicate(int $duplicateId): self
    {
        return new self($duplicateId);
    }
}
