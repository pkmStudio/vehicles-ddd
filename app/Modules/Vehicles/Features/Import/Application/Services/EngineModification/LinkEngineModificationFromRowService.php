<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;
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
     */
    public function __construct(
        private EngineModificationCommandInterface $command,
        private EngineModificationDataFactoryInterface $factory,
    ) {}

    /**
     * Связывает двигатель и модификацию из command import row.
     *
     * Шаги:
     * 1) Собрать raw row array из typed command DTO.
     * 2) Валидировать и преобразовать строку в `EngineModificationData`.
     * 3) Синхронизировать pivot-связь без удаления существующих связей.
     *
     * @throws ImportRowValidationException
     */
    public function linkFromRow(EngineModificationCommandRowDTO $row): void
    {
        $data = $this->factory->make([
            'eng_id' => $row->engId,
            'mod_id' => $row->modId,
            'type' => $row->type,
        ]);

        $this->command->syncWithoutDetaching($data);
    }
}
