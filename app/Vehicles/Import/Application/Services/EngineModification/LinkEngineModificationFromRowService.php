<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\EngineModification;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Vehicles\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;
use Illuminate\Validation\ValidationException;

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
     * @throws ValidationException
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
