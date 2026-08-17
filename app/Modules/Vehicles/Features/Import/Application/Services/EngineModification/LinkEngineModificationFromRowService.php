<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Use-case: связать двигатель с модификацией из строки импорта (пивот engine_modification).
 */
final readonly class LinkEngineModificationFromRowService implements LinkEngineModificationFromRowServiceInterface
{
    /**
     * Инициализирует порты сценария связи двигателя и модификации.
     *
     * Шаги:
     * 1) Сохранить command записи pivot-связи.
     * 2) Сохранить factory валидации и сборки `EngineModificationData`.
     * 3) Сохранить repositories для проверки существования связанных engine/modification.
     */
    public function __construct(
        private EngineModificationCommandInterface $command,
        private EngineModificationDataFactoryInterface $factory,
        private EngineRepositoryInterface $engines,
        private ModificationRepositoryInterface $modifications,
    ) {}

    /**
     * Связывает двигатель и модификацию из import row DTO.
     *
     * Шаги:
     * 1) Передать typed row DTO в factory.
     * 2) Валидировать и преобразовать строку в `EngineModificationData`.
     * 3) Проверить, что engine и modification уже существуют.
     * 4) Добавить pivot-связь только если она еще не существует.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function linkFromRow(EngineModificationRowDTO $row): void
    {
        $data = $this->factory->make($row);

        if ($this->engines->findByEngId($data->engId) === null) {
            throw ImportRowReferenceNotFoundException::withMessage("Двигатель eng_id={$data->engId} не найден.");
        }

        if ($this->modifications->findByModIdAndType($data->modId, $data->type->value) === null) {
            throw ImportRowReferenceNotFoundException::withMessage("Модификация mod_id={$data->modId}, type={$data->type->value} не найдена.");
        }

        $this->command->attachIfMissing($data);
    }
}
