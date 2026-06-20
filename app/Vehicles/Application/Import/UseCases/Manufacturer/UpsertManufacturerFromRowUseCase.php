<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Manufacturer;

use App\Vehicles\Application\Import\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ManufacturerCommandInterface;
use App\Vehicles\Domain\Contracts\Import\UseCases\Manufacturer\UpsertManufacturerFromRowUseCaseInterface;
use App\Vehicles\Domain\Models\Manufacturer;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить производителя из строки импорта (приведение к виду TD).
 */
final readonly class UpsertManufacturerFromRowUseCase implements UpsertManufacturerFromRowUseCaseInterface
{
    public function __construct(
        private ManufacturerCommandInterface $command,
        private ManufacturerDataFactory $factory,
    ) {}

    /**
     * @param  array<int, mixed>  $row
     *
     * @throws ValidationException
     */
    public function execute(array $row): Manufacturer
    {
        $data = $this->factory->make([
            'mfa_id' => $row[0] ?? null,
            'name' => $row[1] ?? null,
            'provider' => 'TD',
        ]);

        return $this->command->upsertByMfaId($data);
    }
}
