<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Application\Import\UseCases\EngineModification\LinkEngineModificationFromRowUseCase;
use App\Vehicles\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\ModelData\EngineModification\EngineModificationData;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class LinkEngineModificationFromRowUseCaseTest extends TestCase
{
    public function test_maps_row_and_links_pivot(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldReceive('syncWithoutDetaching')
            ->once()
            ->with(Mockery::on(fn (EngineModificationData $d) => $d->engId === 1 && $d->modId === 2 && $d->type === 'PC'));

        $useCase = new LinkEngineModificationFromRowUseCase($command, new EngineModificationDataFactory);
        $useCase->execute([1, 2, 'PC']);

        $this->addToAssertionCount(1);
    }

    public function test_invalid_type_throws_validation_exception(): void
    {
        $command = Mockery::mock(EngineModificationCommandInterface::class);
        $command->shouldNotReceive('syncWithoutDetaching');

        $useCase = new LinkEngineModificationFromRowUseCase($command, new EngineModificationDataFactory);

        $this->expectException(ValidationException::class);
        $useCase->execute([1, 2, 'НЕВЕРНО']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
