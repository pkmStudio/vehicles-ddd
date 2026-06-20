<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\EngineModification;

use App\Vehicles\Application\Import\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Domain\Contracts\Commands\EngineModificationCommandInterface;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: связать двигатель с модификацией из строки импорта (пивот engine_modification).
 */
final readonly class LinkEngineModificationFromRowUseCase
{
    public function __construct(
        private EngineModificationCommandInterface $command,
        private EngineModificationDataFactory $factory,
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
