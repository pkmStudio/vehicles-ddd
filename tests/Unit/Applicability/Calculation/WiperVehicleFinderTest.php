<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperVehicleFinder;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use Mockery;
use Tests\TestCase;

final class WiperVehicleFinderTest extends TestCase
{
    public function test_check_adapters_requires_vehicle_adapter_in_all_adapters(): void
    {
        $finder = $this->finder();

        $this->assertTrue($finder->checkAdapters(
            vehicleAdapters: ['H'],
            adapters: new WiperAdaptersDTO(allAdapters: ['H', 'A'], putAdapters: []),
        ));

        $this->assertFalse($finder->checkAdapters(
            vehicleAdapters: ['Z'],
            adapters: new WiperAdaptersDTO(allAdapters: ['H', 'A'], putAdapters: []),
        ));
    }

    public function test_check_adapters_requires_put_adapter_intersection_when_put_adapters_exist(): void
    {
        $finder = $this->finder();

        $this->assertTrue($finder->checkAdapters(
            vehicleAdapters: ['B'],
            adapters: new WiperAdaptersDTO(allAdapters: ['A', 'B'], putAdapters: ['B']),
        ));

        $this->assertFalse($finder->checkAdapters(
            vehicleAdapters: ['A'],
            adapters: new WiperAdaptersDTO(allAdapters: ['A', 'B'], putAdapters: ['B']),
        ));
    }

    private function finder(): WiperVehicleFinder
    {
        return new WiperVehicleFinder(
            vehicles: Mockery::mock(VehiclesApplicabilityClientInterface::class),
            templates: Mockery::mock(TemplatesClientInterface::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
