<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\ManufacturerDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer\UpsertManufacturerFromRowService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

final class UpsertManufacturerFromRowServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: строка маппится в ManufacturerData, provider всегда приводится к
     * TD (TecDoc — единственный реальный источник консольного каскада), и уходит в
     * Command::create.
     *
     * Шаги:
     * 1. Мокает Command::create — ожидает данные с mfaId/name из строки и provider=TD.
     * 2. Зовёт upsertFromRow() с валидным ManufacturerCommandRowDTO.
     * 3. Проверяет, что вернулся именно ожидаемый результат Command.
     */
    public function test_maps_row_and_upserts_with_td_provider(): void
    {
        Event::fake([ManufacturerCreated::class]);

        $expected = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::TD, id: 3);

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturnNull();

        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (ManufacturerData $d) => $d->mfaId === 10 && $d->name === 'Skoda' && $d->provider === ProviderEnum::TD))
            ->andReturn($expected);

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory, $manufacturers);

        $this->assertSame($expected, $service->upsertFromRow(new ManufacturerCommandRowDTO(mfaId: 10, name: 'Skoda')));

        Event::assertDispatched(ManufacturerCreated::class);
    }

    /**
     * Проверяет, что отсутствие обязательного name отклоняется валидацией до записи.
     *
     * Шаги:
     * 1. Мокает Command — ожидает, что create НЕ вызовется.
     * 2. Зовёт upsertFromRow() со строкой, где name=null.
     * 3. Ожидает ImportRowValidationException.
     */
    public function test_missing_name_throws_validation_exception(): void
    {
        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldNotReceive('create');

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldNotReceive('findByMfaId');

        $service = new UpsertManufacturerFromRowService($command, new ManufacturerDataFactory, $manufacturers);

        $this->expectException(ImportRowValidationException::class);
        $service->upsertFromRow(new ManufacturerCommandRowDTO(mfaId: 10, name: null));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
