<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\ModificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Modification\UpsertModificationFromRowService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Modification\ModificationCreated;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

final class UpsertModificationFromRowServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: ТС находится по ms_id из строки, модификация маппится с
     * унаследованным type/vehicleId от найденного ТС и уходит в Command.
     *
     * Шаги:
     * 1. Мокает VehicleRepositoryInterface::firstByMsId — возвращает VehicleData(id=9, type=PC).
     * 2. Мокает Command::upsertByModIdAndType — ожидает данные с modId/msId из строки и
     *    type/vehicleId, унаследованными от найденного ТС.
     * 3. Зовёт upsertFromRow() с валидным ModificationCommandRowDTO.
     * 4. Проверяет, что вернулся именно ожидаемый результат Command.
     */
    public function test_resolves_vehicle_and_upserts_modification(): void
    {
        Event::fake([ModificationCreated::class]);

        $vehicle = new VehicleData(
            msId: 200,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
            id: 9,
        );
        $expected = new ModificationData(modId: 50, type: VehicleTypeEnum::PC, vehicleId: 9, msId: 200);

        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturn($vehicle);

        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldReceive('firstByModIdAndType')->once()->with(50, 'PC')->andReturnNull();

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldReceive('upsertByModIdAndType')
            ->once()
            ->with(Mockery::on(fn (ModificationData $d) => $d->modId === 50 && $d->msId === 200 && $d->type === VehicleTypeEnum::PC && $d->vehicleId === 9))
            ->andReturn($expected);

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles, $modifications);

        $this->assertSame($expected, $service->upsertFromRow($this->validRow()));

        Event::assertDispatched(ModificationCreated::class);
    }

    /**
     * Проверяет, что при отсутствии ТС с таким ms_id модификация вообще не записывается —
     * нет смысла заводить модификацию-сироту без родителя.
     *
     * Шаги:
     * 1. Мокает VehicleRepositoryInterface::firstByMsId — возвращает null.
     * 2. Мокает Command — ожидает, что upsertByModIdAndType НЕ вызовется.
     * 3. Зовёт upsertFromRow() и проверяет, что результат null.
     */
    public function test_returns_null_when_vehicle_not_found(): void
    {
        $vehicles = Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles->shouldReceive('firstByMsId')->once()->with(200)->andReturnNull();

        $command = Mockery::mock(ModificationCommandInterface::class);
        $command->shouldNotReceive('upsertByModIdAndType');

        $modifications = Mockery::mock(ModificationRepositoryInterface::class);
        $modifications->shouldNotReceive('firstByModIdAndType');

        $service = new UpsertModificationFromRowService($command, new ModificationDataFactory, $vehicles, $modifications);

        $this->assertNull($service->upsertFromRow($this->validRow()));
    }

    private function validRow(): ModificationCommandRowDTO
    {
        return new ModificationCommandRowDTO(
            msId: 200,
            modId: 50,
            yearFrom: null,
            yearTo: null,
            description: null,
            powerPs: null,
            powerKw: null,
            engineType: null,
            gearType: null,
            driveType: null,
            brakeSystemType: null,
            numberOfCylinders: null,
            capacityLt: null,
            type: 'PC',
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
