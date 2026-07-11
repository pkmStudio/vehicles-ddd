<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\Engine;

/**
 * Исход назначения группы двигателю по коду — адаптер транслирует его в отчёт об ошибках.
 */
final readonly class AssignEngineGroupResultDTO
{
    public function __construct(
        public bool $found,
        public bool $reassigned = false,
        public ?int $previousGroupId = null,
    ) {}
}
