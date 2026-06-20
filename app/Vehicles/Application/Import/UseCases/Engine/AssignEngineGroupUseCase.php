<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Contracts\Import\UseCases\Engine\AssignEngineGroupUseCaseInterface;
use App\Vehicles\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\DTOs\AssignEngineGroupResult;

/**
 * Use-case: назначить двигателю (по коду) группу. Возвращает исход для отчёта:
 * найден ли двигатель и не переназначаем ли мы его из другой непустой группы.
 */
final readonly class AssignEngineGroupUseCase implements AssignEngineGroupUseCaseInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
    ) {}

    public function execute(string $code, int $groupId): AssignEngineGroupResult
    {
        $engine = $this->engines->firstByCodeEngine($code);

        if (! $engine) {
            return new AssignEngineGroupResult(found: false);
        }

        $previousGroupId = $engine->group_id;
        $reassigned = $previousGroupId !== null && $previousGroupId !== $groupId;

        $this->command->setGroupId($engine, $groupId);

        return new AssignEngineGroupResult(
            found: true,
            reassigned: $reassigned,
            previousGroupId: $previousGroupId,
        );
    }
}
