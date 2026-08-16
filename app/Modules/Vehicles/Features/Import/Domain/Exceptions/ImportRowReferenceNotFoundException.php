<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Exceptions;

use DomainException;

/**
 * Ошибка строки импорта, которая ссылается на отсутствующую уже существующую сущность.
 */
final class ImportRowReferenceNotFoundException extends DomainException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }

    /**
     * Вернуть ошибку в формате Laravel Excel failure.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return [$this->getMessage()];
    }
}
