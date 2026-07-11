<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Manufacturer;

use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Vehicles\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
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
     * @throws ValidationException
     */
    public function upsertFromRow(ManufacturerCommandRowDTO $row): ManufacturerData
    {
        $data = $this->factory->make([
            'mfa_id' => $row->mfaId,
            'name' => $row->name,
            'provider' => ProviderEnum::TD->value,
        ]);

        return $this->command->upsertByMfaId($data);
    }
}
