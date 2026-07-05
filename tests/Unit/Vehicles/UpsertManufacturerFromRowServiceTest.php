<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Application\Import\Services\Manufacturer\UpsertManufacturerFromRowService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ManufacturerCommandInterface;
use App\Vehicles\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Domain\Models\Manufacturer;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class UpsertManufacturerFromRowServiceTest extends TestCase
{
    public function test_maps_row_and_upserts_with_td_provider(): void
    {
        $expected = new Manufacturer;

        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldReceive('upsertByMfaId')
            ->once()
            ->with(Mockery::on(fn (ManufacturerData $d) => $d->mfaId === 10 && $d->name === 'Skoda' && $d->provider === 'TD'))
            ->andReturn($expected);

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory);

        $this->assertSame($expected, $service->upsertFromRow([10, 'Skoda']));
    }

    public function test_missing_name_throws_validation_exception(): void
    {
        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldNotReceive('upsertByMfaId');

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory);

        $this->expectException(ValidationException::class);
        $service->upsertFromRow([10]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
