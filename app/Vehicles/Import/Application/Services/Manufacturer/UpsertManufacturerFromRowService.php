<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Manufacturer;

use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use Illuminate\Validation\ValidationException;

/**
 * Use-case: создать/обновить производителя из строки импорта (приведение к виду TD).
 */
final readonly class UpsertManufacturerFromRowService implements UpsertManufacturerFromRowServiceInterface
{
    public function __construct(
        private ManufacturerCommandInterface $command,
        private ManufacturerDataFactoryInterface $factory,
    ) {}

    /**
     * @param  array<int, mixed>  $row
     *
     * @throws ValidationException
     */
    public function upsertFromRow(array $row): ManufacturerData
    {
        $data = $this->factory->make([
            'mfa_id' => $row[0] ?? null,
            'name' => $row[1] ?? null,
            'provider' => 'TD',
        ]);

        return $this->command->upsertByMfaId($data);
    }
}
