<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Exceptions;

use DomainException;

/**
 * Ошибка валидации одной строки импорта без зависимости Domain/Application от Laravel Validator.
 */
final class ImportRowValidationException extends DomainException
{
    /**
     * @param  array<int, string>  $errors
     */
    private function __construct(
        private readonly array $errors,
    ) {
        parent::__construct(implode(' ', $errors));
    }

    /**
     * @param  array<string, array<int, string>|string>  $messages
     */
    public static function fromMessages(array $messages): self
    {
        $errors = [];

        array_walk_recursive($messages, static function (mixed $message) use (&$errors): void {
            if (is_scalar($message)) {
                $errors[] = (string) $message;
            }
        });

        return new self($errors === [] ? ['Ошибка валидации строки импорта.'] : $errors);
    }

    public static function fromMessage(string $message): self
    {
        return new self([$message]);
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
