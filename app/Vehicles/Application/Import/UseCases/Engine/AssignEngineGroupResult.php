<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

/**
 * Исход назначения группы двигателю по коду — адаптер транслирует его в отчёт об ошибках.
 */
final readonly class AssignEngineGroupResult
{
    public function __construct(
        public bool $found,
        public bool $reassigned = false,
        public ?int $previousGroupId = null,
    ) {}
}
