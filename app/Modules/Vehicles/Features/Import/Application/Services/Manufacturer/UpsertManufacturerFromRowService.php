<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
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
