<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Factories\ManufacturerDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer\UpsertManufacturerFromSheetService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Events\Manufacturer\ManufacturerCreated;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * ManufacturerSheetRowDTO строгий (mfa_id/name/provider — не nullable): "пустое поле" здесь
 * больше не воспроизводимо на уровне DTO, эта проверка теперь у ManufacturerSheetRowMapper
 * (см. ManufacturerSheetRowMapperTest). Сервис доверяет DTO и просто передаёт provider как есть.
 */
final class UpsertManufacturerFromSheetServiceTest extends TestCase
{
    /**
     * Проверяет, что provider из строки (OD) доходит до Command::create как есть —
     * в отличие от консольного TecDoc-каскада, здесь provider не хардкодится.
     */
    public function test_uses_provider_from_row_as_is(): void
    {
        Event::fake([ManufacturerCreated::class]);

        $expected = new ManufacturerData(mfaId: 10, name: 'Skoda', provider: ProviderEnum::OD, id: 3);

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldReceive('findByMfaId')->once()->with(10)->andReturnNull();

        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (ManufacturerData $d) => $d->mfaId === 10 && $d->name === 'Skoda' && $d->provider === ProviderEnum::OD))
            ->andReturn($expected);

        $service = new UpsertManufacturerFromSheetService($command, new ManufacturerDataFactory, $manufacturers);

        $this->assertSame(
            $expected,
            $service->upsertFromRow(new ManufacturerSheetRowDTO(mfaId: 10, name: 'Skoda', provider: 'OD')),
        );

        Event::assertDispatched(ManufacturerCreated::class);
    }

    /**
     * Проверяет, что provider, не входящий в ProviderEnum (TD/OD), отклоняется валидацией
     * до записи — DTO гарантирует непустую строку, но не гарантирует, что это валидный provider.
     */
    public function test_invalid_provider_throws_validation_exception(): void
    {
        $command = Mockery::mock(ManufacturerCommandInterface::class);
        $command->shouldNotReceive('create');

        $manufacturers = Mockery::mock(ManufacturerRepositoryInterface::class);
        $manufacturers->shouldNotReceive('findByMfaId');

        $service = new UpsertManufacturerFromSheetService($command, new ManufacturerDataFactory, $manufacturers);

        $this->expectException(ImportRowValidationException::class);
        $service->upsertFromRow(new ManufacturerSheetRowDTO(mfaId: 10, name: 'Skoda', provider: 'XX'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
