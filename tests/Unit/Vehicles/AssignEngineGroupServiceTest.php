<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles;

use App\Vehicles\Import\Application\Services\Engine\AssignEngineGroupService;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use Mockery;
use Tests\TestCase;

final class AssignEngineGroupServiceTest extends TestCase
{
    public function test_assigns_group_to_engine_without_previous_group(): void
    {
        $engine = new EngineData(engId: 555, id: 1, groupId: null);

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByCodeEngine')->once()->with('M54B30')->andReturn($engine);

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('setGroupId')->once()->with($engine, 7);

        $result = (new AssignEngineGroupService($engines, $command))->assignGroup('M54B30', 7);

        $this->assertTrue($result->found);
        $this->assertFalse($result->reassigned);
        $this->assertNull($result->previousGroupId);
    }

    public function test_flags_reassignment_when_group_changes(): void
    {
        $engine = new EngineData(engId: 555, id: 1, groupId: 3);

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByCodeEngine')->once()->with('M54B30')->andReturn($engine);

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('setGroupId')->once()->with($engine, 7);

        $result = (new AssignEngineGroupService($engines, $command))->assignGroup('M54B30', 7);

        $this->assertTrue($result->found);
        $this->assertTrue($result->reassigned);
        $this->assertSame(3, $result->previousGroupId);
    }

    public function test_reports_not_found_and_skips_command(): void
    {
        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByCodeEngine')->once()->with('UNKNOWN')->andReturnNull();

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldNotReceive('setGroupId');

        $result = (new AssignEngineGroupService($engines, $command))->assignGroup('UNKNOWN', 7);

        $this->assertFalse($result->found);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
