<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;

/**
 * Use-case: назначить двигателю (по коду) группу. Возвращает исход для отчёта:
 * найден ли двигатель и не переназначаем ли мы его из другой непустой группы.
 */
final readonly class AssignEngineGroupService implements AssignEngineGroupServiceInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
    ) {}

    public function assignGroup(string $code, int $groupId): AssignEngineGroupResultDTO
    {
        $engine = $this->engines->firstByCodeEngine($code);

        if (! $engine) {
            return new AssignEngineGroupResultDTO(found: false);
        }

        $previousGroupId = $engine->groupId;
        $reassigned = $previousGroupId !== null && $previousGroupId !== $groupId;

        $this->command->setGroupId($engine, $groupId);

        return new AssignEngineGroupResultDTO(
            found: true,
            reassigned: $reassigned,
            previousGroupId: $previousGroupId,
        );
    }
}
