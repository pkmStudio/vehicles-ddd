<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Import\Application\Services\EngineModification\LinkEngineModificationFromRowService;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\ModelData\EngineModification\EngineModificationData;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class LinkEngineModificationFromRowServiceTest extends TestCase
{
    public function test_maps_row_and_links_pivot(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldReceive('syncWithoutDetaching')
            ->once()
            ->with(Mockery::on(fn (EngineModificationData $d) => $d->engId === 1 && $d->modId === 2 && $d->type === 'PC'));

        $service = new LinkEngineModificationFromRowService($command, new EngineModificationDataFactory);
        $service->linkFromRow([1, 2, 'PC']);

        $this->addToAssertionCount(1);
    }

    public function test_invalid_type_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldNotReceive('syncWithoutDetaching');

        $service = new LinkEngineModificationFromRowService($command, new EngineModificationDataFactory);

        $this->expectException(ValidationException::class);
        $service->linkFromRow([1, 2, 'НЕВЕРНО']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
