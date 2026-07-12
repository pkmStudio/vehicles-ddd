<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Vehicles\Import\Application\Factories\EngineModificationDataFactory;
use App\Vehicles\Import\Application\Services\EngineModification\LinkEngineModificationFromRowService;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\EngineModificationData;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class LinkEngineModificationFromRowServiceTest extends TestCase
{
    /**
     * Проверяет happy-path: валидная строка маппится в EngineModificationData и уходит в
     * Command::syncWithoutDetaching (pivot-запись engine_modification).
     *
     * Шаги:
     * 1. Мокает EngineModificationCommandInterface::syncWithoutDetaching — ожидает вызов с
     *    данными, где engId/modId/type совпадают со строкой.
     * 2. Зовёт linkFromRow() с валидным EngineModificationCommandRowDTO.
     */
    public function test_maps_row_and_links_pivot(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldReceive('syncWithoutDetaching')
            ->once()
            ->with(Mockery::on(fn (EngineModificationData $d) => $d->engId === 1 && $d->modId === 2 && $d->type === 'PC'));

        $service = new LinkEngineModificationFromRowService($command, new EngineModificationDataFactory);
        $service->linkFromRow(new EngineModificationCommandRowDTO(engId: 1, modId: 2, type: 'PC'));

        $this->addToAssertionCount(1);
    }

    /**
     * Проверяет, что невалидное значение type (не входит в допустимый набор) валится
     * ValidationException'ом до записи, а не уходит в Command как есть.
     *
     * Шаги:
     * 1. Мокает Command — ожидает, что syncWithoutDetaching НЕ вызовется.
     * 2. Зовёт linkFromRow() со строкой, где type='НЕВЕРНО'.
     * 3. Ожидает ValidationException.
     */
    public function test_invalid_type_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldNotReceive('syncWithoutDetaching');

        $service = new LinkEngineModificationFromRowService($command, new EngineModificationDataFactory);

        $this->expectException(ValidationException::class);
        $service->linkFromRow(new EngineModificationCommandRowDTO(engId: 1, modId: 2, type: 'НЕВЕРНО'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
