<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

/**
 * Use-case: назначить двигателю (по коду) группу. Возвращает исход для отчёта:
 * найден ли двигатель и не переназначаем ли мы его из другой непустой группы.
 *
 * @deprecated Фича группировки двигателей по кросс-кодам ещё на большой бизнес-доработке —
 *   правила назначения группы не финальны (см. EngineCrossImport, который сюда обращается).
 */
final readonly class AssignEngineGroupService implements AssignEngineGroupServiceInterface
{
    /**
     * Инициализирует read/write порты двигателя.
     *
     * Шаги:
     * 1) Сохранить repository для поиска двигателя по коду.
     * 2) Сохранить command для записи group id.
     */
    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineCommandInterface $command,
    ) {}

    /**
     * Назначает группу двигателю по его коду.
     *
     * Шаги:
     * 1) Найти двигатель по коду.
     * 2) Если двигатель не найден — вернуть result `found=false`.
     * 3) Определить, будет ли непустая группа переназначена.
     * 4) Собрать минимальный `EngineData` с новым group id и записать его через command.
     * 5) Вернуть result с прежним group id и флагом reassigned.
     */
    public function assignGroup(string $code, int $groupId): AssignEngineGroupResultDTO
    {
        $engine = $this->engines->findByCodeEngine($code);

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
