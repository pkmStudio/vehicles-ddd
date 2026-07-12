<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;
use App\Vehicles\Import\Domain\ModelData\EngineData;

/**
 * Use-case: назначить двигателю (по коду) группу. Возвращает исход для отчёта:
 * найден ли двигатель и не переназначаем ли мы его из другой непустой группы.
 *
 * @deprecated Фича группировки двигателей по кросс-кодам ещё на большой бизнес-доработке —
 *   правила назначения группы не финальны (см. EngineCrossImport, который сюда обращается).
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

        $updatedEngine = new EngineData(engId: $engine->engId, id: $engine->id, groupId: $groupId);
        $this->command->setGroupId($updatedEngine);

        return new AssignEngineGroupResultDTO(
            found: true,
            reassigned: $reassigned,
            previousGroupId: $previousGroupId,
        );
    }
}
