<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services\EngineModification;

use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineModificationCommandInterface;
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
    public function execute(array $row): void
    {
        $data = $this->factory->make([
            'eng_id' => $row[0] ?? null,
            'mod_id' => $row[1] ?? null,
            'type' => $row[2] ?? null,
        ]);

        $this->command->syncWithoutDetaching($data);
    }
}
