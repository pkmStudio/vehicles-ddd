<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\UseCases\Engine\UpdateEngineEditableFieldsUseCase;
use App\Vehicles\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Models\Engine;
use Mockery;
use Tests\TestCase;

final class UpdateEngineEditableFieldsUseCaseTest extends TestCase
{
    public function test_forwards_eng_id_and_attributes_to_command(): void
    {
        $expected = new Engine;
        $attributes = ['engine_capacity' => '2979', 'cylinder_count' => 6];

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('updateEditableByEngId')
            ->once()
            ->with(101, $attributes)
            ->andReturn($expected);

        $useCase = new UpdateEngineEditableFieldsUseCase($command);

        $this->assertSame($expected, $useCase->execute(101, $attributes));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
