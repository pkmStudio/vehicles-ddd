<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification;

/**
 * Передает результат применения правил записи details спецификации.
 */
final readonly class PartSpecificationDetailsWriteResultDTO
{
    /**
     * Инициализирует immutable-результат проверки и нормализации details.
     *
     * @param  array<string, mixed>  $details
     * @param  array<int, array{field: string, rule: string, message: string}>  $errors
     */
    public function __construct(
        public bool $valid,
        public array $details,
        public array $errors = [],
    ) {}
}
