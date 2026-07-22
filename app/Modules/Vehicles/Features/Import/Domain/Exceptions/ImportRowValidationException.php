<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Exceptions;

use InvalidArgumentException;

/**
 * Ошибка валидации одной строки импорта без зависимости Domain/Application от Laravel Validator.
 */
final class ImportRowValidationException extends InvalidArgumentException
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

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
