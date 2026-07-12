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
    /**
     * Проверяет базовый сценарий: у двигателя ещё не было группы — назначаем новую.
     *
     * Шаги:
     * 1. Мокает EngineRepositoryInterface::firstByCodeEngine — возвращает EngineData без group_id.
     * 2. Мокает EngineCommandInterface::setGroupId — ожидает вызов с Data (engId/id того же
     *    двигателя, groupId новой группы 7).
     * 3. Зовёт assignGroup('M54B30', 7).
     * 4. Проверяет результат: found=true, reassigned=false, previousGroupId=null.
     */
    public function test_assigns_group_to_engine_without_previous_group(): void
    {
        $engine = new EngineData(engId: 555, id: 1, groupId: null);

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByCodeEngine')->once()->with('M54B30')->andReturn($engine);

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('setGroupId')
            ->once()
            ->with(Mockery::on(fn (EngineData $data): bool => $data->engId === 555 && $data->id === 1 && $data->groupId === 7))
            ->andReturn(new EngineData(engId: 555, id: 1, groupId: 7));

        $result = (new AssignEngineGroupService($engines, $command))->assignGroup('M54B30', 7);

        $this->assertTrue($result->found);
        $this->assertFalse($result->reassigned);
        $this->assertNull($result->previousGroupId);
    }

    /**
     * Проверяет переназначение: у двигателя уже была другая группа — сервис должен явно
     * пометить это как reassignment, а не тихо перезаписать.
     *
     * Шаги:
     * 1. Мокает EngineRepositoryInterface::firstByCodeEngine — возвращает EngineData с group_id=3.
     * 2. Мокает EngineCommandInterface::setGroupId — ожидает вызов с Data с новой группой 7.
     * 3. Зовёт assignGroup('M54B30', 7).
     * 4. Проверяет результат: found=true, reassigned=true, previousGroupId=3.
     */
    public function test_flags_reassignment_when_group_changes(): void
    {
        $engine = new EngineData(engId: 555, id: 1, groupId: 3);

        $engines = Mockery::mock(EngineRepositoryInterface::class);
        $engines->shouldReceive('firstByCodeEngine')->once()->with('M54B30')->andReturn($engine);

        $command = Mockery::mock(EngineCommandInterface::class);
        $command->shouldReceive('setGroupId')
            ->once()
            ->with(Mockery::on(fn (EngineData $data): bool => $data->engId === 555 && $data->id === 1 && $data->groupId === 7))
            ->andReturn(new EngineData(engId: 555, id: 1, groupId: 7));

        $result = (new AssignEngineGroupService($engines, $command))->assignGroup('M54B30', 7);

        $this->assertTrue($result->found);
        $this->assertTrue($result->reassigned);
        $this->assertSame(3, $result->previousGroupId);
    }

    /**
     * Проверяет, что при отсутствии двигателя с таким кодом Command вообще не вызывается —
     * не бывает случайной записи по несуществующей сущности.
     *
     * Шаги:
     * 1. Мокает EngineRepositoryInterface::firstByCodeEngine — возвращает null.
     * 2. Мокает EngineCommandInterface — ожидает, что setGroupId НЕ вызовется.
     * 3. Зовёт assignGroup('UNKNOWN', 7).
     * 4. Проверяет результат: found=false.
     */
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
