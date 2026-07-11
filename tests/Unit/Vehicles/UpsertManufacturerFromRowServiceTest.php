<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Factories\ManufacturerDataFactory;
use App\Vehicles\Import\Application\Services\Manufacturer\UpsertManufacturerFromRowService;
use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class UpsertManufacturerFromRowServiceTest extends TestCase
{
    public function test_maps_row_and_upserts_with_td_provider(): void
    {
        $expected = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::TD, id: 3);

        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldReceive('upsertByMfaId')
            ->once()
            ->with(Mockery::on(fn (ManufacturerData $d) => $d->mfaId === 10 && $d->name === 'Skoda' && $d->provider === ProviderEnum::TD))
            ->andReturn($expected);

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory);

        $this->assertSame($expected, $service->upsertFromRow(new ManufacturerCommandRowDTO(mfaId: 10, name: 'Skoda')));
    }

    public function test_missing_name_throws_validation_exception(): void
    {
        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldNotReceive('upsertByMfaId');

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory);

        $this->expectException(ValidationException::class);
        $service->upsertFromRow(new ManufacturerCommandRowDTO(mfaId: 10, name: null));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
