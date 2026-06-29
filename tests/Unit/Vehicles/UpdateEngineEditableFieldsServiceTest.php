<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Application\Import\Services\Engine\UpdateEngineEditableFieldsService;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Models\Engine;
use Mockery;
use Tests\TestCase;

final class UpdateEngineEditableFieldsServiceTest extends TestCase
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

        $service = new UpdateEngineEditableFieldsService($command);

        $this->assertSame($expected, $service->execute(101, $attributes));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
