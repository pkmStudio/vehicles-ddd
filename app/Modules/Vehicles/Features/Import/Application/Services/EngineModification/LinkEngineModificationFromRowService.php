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
    public function __construct(
        private EngineModificationCommandInterface $command,
        private EngineModificationDataFactoryInterface $factory,
    ) {}

    /**
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
