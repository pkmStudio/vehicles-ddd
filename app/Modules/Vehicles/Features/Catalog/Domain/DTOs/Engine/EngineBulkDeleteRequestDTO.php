<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

/**
 * DTO входящей команды на массовое удаление двигателей.
 */
final readonly class EngineBulkDeleteRequestDTO
{
    /**
     * Получает автора операции, correlation id и список eng_id двигателей.
     *
     * @param  list<int>  $engIds
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $engIds,
    ) {}
}
