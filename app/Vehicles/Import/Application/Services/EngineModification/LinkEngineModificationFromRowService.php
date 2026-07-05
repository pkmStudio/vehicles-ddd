<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\EngineModification;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
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
     * @param  array<int, mixed>  $row
     *
     * @throws ValidationException
     */
    public function linkFromRow(array $row): void
    {
        $data = $this->factory->make([
            'eng_id' => $row[0] ?? null,
            'mod_id' => $row[1] ?? null,
            'type' => $row[2] ?? null,
        ]);

        $this->command->syncWithoutDetaching($data);
    }
}
