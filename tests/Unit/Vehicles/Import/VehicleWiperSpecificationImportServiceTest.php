<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicles\Import;

use App\Templates\Application\WiperSpecificationService;
use App\Vehicles\Import\Application\Services\Vehicle\VehicleWiperSpecificationImportService;
use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Import\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\FeatureValueData;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class VehicleWiperSpecificationImportServiceTest extends TestCase
{
    private function service(
        PartSpecificationRepositoryInterface $specs,
        PartSpecificationCommandInterface $command,
        ?FeatureValueRepositoryInterface $featureValues = null,
        ?VehicleRepositoryInterface $vehicles = null,
    ): VehicleWiperSpecificationImportService {
        $vehicles ??= Mockery::mock(VehicleRepositoryInterface::class);
        $vehicles
            ->shouldReceive('firstByMsId')
            ->with(77)
            ->andReturn($this->vehicleData());

        return new VehicleWiperSpecificationImportService(
            $featureValues ?? Mockery::mock(FeatureValueRepositoryInterface::class),
            $specs,
            $command,
            new WiperSpecificationService,
            $vehicles,
        );
    }

    private function vehicleData(): VehicleData
    {
        return new VehicleData(
            msId: 77,
            mfaId: 10,
            manufacturerId: 3,
            name: 'Octavia',
            type: VehicleTypeEnum::PC,
            steeringType: SteeringTypeEnum::LEFT,
            typeCarcase: CarcaseTypeEnum::HATCHBACK,
            provider: ProviderEnum::TD,
            id: 77,
        );
    }

    private function wiperRow(array $details, int $msId = 77, ?string $featureValueName = null, ?string $name = null, ?string $text = null): VehicleWiperSheetRowDTO
    {
        return new VehicleWiperSheetRowDTO(
            msId: $msId,
            templateSlug: DetailTemplateEnum::WIPER->value,
            featureValueName: $featureValueName,
            name: $name,
            text: $text,
            details: $details,
        );
    }

    /**
     * Проверяет базовый сценарий создания: если для front и back нет ни точного совпадения,
     * ни существующей записи стороны — создаётся по одной новой PartSpecification на сторону.
     *
     * Шаги:
     * 1. Мокает Repository: firstByVehicleTemplateSideAndDetails и forVehicleTemplateAndSide
     *    для front/back возвращают null/пустую коллекцию (совпадений и существующих нет).
     * 2. Мокает Command::create — ожидает ровно 2 вызова (по одному на сторону), update не
     *    ожидается вообще.
     * 3. Зовёт upsertFromRow() со строкой с обеими сторонами (front+back в details).
     * 4. Проверяет, что create() вызван именно для 'front' и 'back' (в этом порядке).
     */
    public function test_creates_one_spec_per_side_when_none_exist(): void
    {
        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'back', ['back' => ['adapter_type_rear' => ['B1']]])
            ->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')
            ->andReturn(new Collection);
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'back')
            ->andReturn(new Collection);

        $created = [];
        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->twice()
            ->with(Mockery::on(function (PartSpecificationData $d) use (&$created) {
                $created[] = array_key_first($d->details);

                return true;
            }))
            ->andReturnUsing(fn (PartSpecificationData $d) => $d);
        $command->shouldNotReceive('update');

        $details = [
            'front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2],
            'back' => ['adapter_type_rear' => ['B1']],
        ];

        $this->service($specs, $command)->upsertFromRow($this->wiperRow($details));

        $this->assertSame(['front', 'back'], $created);
    }

    /**
     * Проверяет сценарий точного совпадения: если для стороны уже есть PartSpecification с
     * теми же details — сервис обновляет именно её (по id), а не создаёт дубликат.
     *
     * Шаги:
     * 1. Мокает Repository::firstByVehicleTemplateSideAndDetails('front', ...) — возвращает
     *    существующую спецификацию (id=5) с точно такими же details.
     * 2. Мокает Command::update — ожидает вызов с этим же id=5, create не ожидается.
     * 3. Зовёт upsertFromRow() со строкой только с front-стороной.
     */
    public function test_updates_existing_side(): void
    {
        $existing = new PartSpecificationData(
            partableType: PartableTypeEnum::VEHICLE->value,
            partableId: 77,
            template: DetailTemplateEnum::WIPER,
            details: ['front' => ['adapter_type_front' => ['A1']]],
            id: 5,
        );

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1']]])
            ->andReturn($existing);
        $specs->shouldNotReceive('forVehicleTemplateAndSide');

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('update')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->id === 5))
            ->andReturnUsing(fn (PartSpecificationData $d) => $d);
        $command->shouldNotReceive('create');

        // только front
        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command)->upsertFromRow($this->wiperRow($details));

        $this->addToAssertionCount(1);
    }

    /**
     * Проверяет промежуточный сценарий: точного совпадения details нет, но для стороны уже
     * есть ровно одна существующая запись — сервис обновляет её (новыми details), а не
     * создаёт вторую запись для той же стороны.
     *
     * Шаги:
     * 1. Мокает Repository::firstByVehicleTemplateSideAndDetails — не находит точное
     *    совпадение (null).
     * 2. Мокает Repository::forVehicleTemplateAndSide('front') — возвращает коллекцию с
     *    ровно одной существующей записью (id=5, другие details).
     * 3. Мокает Command::update — ожидает вызов с этим же id=5, create не ожидается.
     * 4. Зовёт upsertFromRow() со строкой с новыми front-details.
     */
    public function test_updates_single_existing_side_when_exact_details_are_missing(): void
    {
        $existing = new PartSpecificationData(
            partableType: PartableTypeEnum::VEHICLE->value,
            partableId: 77,
            template: DetailTemplateEnum::WIPER,
            details: ['front' => ['adapter_type_front' => ['OLD']]],
            id: 5,
        );

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1']]])
            ->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front')
            ->andReturn(new Collection([$existing]));

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('update')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->id === 5))
            ->andReturnUsing(fn (PartSpecificationData $d) => $d);
        $command->shouldNotReceive('create');

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command)->upsertFromRow($this->wiperRow($details));

        $this->addToAssertionCount(1);
    }

    /**
     * Проверяет, что featureValueName из строки резолвится в featureValueId через
     * FeatureValueRepository и попадает в создаваемую PartSpecification.
     *
     * Шаги:
     * 1. Мокает FeatureValueRepositoryInterface::firstByName('Левый руль') — возвращает
     *    FeatureValueData(id=9).
     * 2. Мокает Repository — совпадений/существующих записей нет (ветка создания).
     * 3. Мокает Command::create — ожидает вызов с featureValueId=9.
     * 4. Зовёт upsertFromRow() со строкой с featureValueName='Левый руль'.
     */
    public function test_resolves_feature_value_by_name(): void
    {
        $fv = new FeatureValueData(featureId: 1, name: 'Левый руль', shortCode: 'L', id: 9);
        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldReceive('firstByName')->once()->with('Левый руль')->andReturn($fv);

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()->andReturnNull();
        $specs->shouldReceive('forVehicleTemplateAndSide')->once()->andReturn(new Collection);

        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->once()
            ->with(Mockery::on(fn (PartSpecificationData $d) => $d->featureValueId === 9))
            ->andReturnUsing(fn (PartSpecificationData $d) => $d);

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command, $featureValues)
            ->upsertFromRow($this->wiperRow($details, featureValueName: 'Левый руль'));

        $this->addToAssertionCount(1);
    }

    /**
     * Проверяет грабли из ARCHITECTURE.md/EventAudit.md: несуществующее имя особенности не
     * должно тихо создавать запись без featureValueId — сервис обязан явно упасть с понятным
     * сообщением, а не молчать.
     *
     * Шаги:
     * 1. Мокает FeatureValueRepositoryInterface::firstByName — возвращает null для
     *    несуществующего имени.
     * 2. Зовёт upsertFromRow() со строкой с featureValueName='Неизвестная особенность'.
     * 3. Ожидает RuntimeException с точным текстом «Особенность не найдена...».
     */
    public function test_throws_when_feature_value_name_not_found(): void
    {
        $featureValues = Mockery::mock(FeatureValueRepositoryInterface::class);
        $featureValues->shouldReceive('firstByName')->once()->with('Неизвестная особенность')->andReturnNull();

        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $command = Mockery::mock(PartSpecificationCommandInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Особенность "Неизвестная особенность" не найдена. Сначала импортируйте особенности.');

        $details = ['front' => ['adapter_type_front' => ['A1']]];

        $this->service($specs, $command, $featureValues)
            ->upsertFromRow($this->wiperRow($details, featureValueName: 'Неизвестная особенность'));
    }

    /**
     * Проверяет бизнес-правило разворачивания множественных адаптеров: если front-сторона
     * содержит несколько значений adapter_type_front (['A1','A2']), для каждого создаётся
     * своя отдельная PartSpecification — они не схлопываются в одну запись и не пытаются
     * обновить существующую (fallback update здесь неприменим, т.к. вариантов несколько).
     *
     * Шаги:
     * 1. Мокает Repository::firstByVehicleTemplateSideAndDetails — не находит совпадений ни
     *    для варианта A1, ни для A2; forVehicleTemplateAndSide не ожидается вообще (сервис не
     *    должен даже пытаться искать «единственную существующую» при множественных адаптерах).
     * 2. Мокает Command::create — ожидает ровно 2 вызова, update не ожидается.
     * 3. Зовёт upsertFromRow() со строкой, где adapter_type_front=['A1','A2'].
     * 4. Проверяет, что созданы записи именно для A1 и для A2 по отдельности.
     */
    public function test_creates_separate_specs_for_multiple_front_adapters_without_fallback_update(): void
    {
        $specs = Mockery::mock(PartSpecificationRepositoryInterface::class);
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A1'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldReceive('firstByVehicleTemplateSideAndDetails')->once()
            ->with(77, DetailTemplateEnum::WIPER, 'front', ['front' => ['adapter_type_front' => ['A2'], 'count_wipers' => 2]])
            ->andReturnNull();
        $specs->shouldNotReceive('forVehicleTemplateAndSide');

        $createdAdapters = [];
        $command = Mockery::mock(PartSpecificationCommandInterface::class);
        $command->shouldReceive('create')->twice()
            ->with(Mockery::on(function (PartSpecificationData $data) use (&$createdAdapters): bool {
                $createdAdapters[] = $data->details['front']['adapter_type_front'];

                return true;
            }))
            ->andReturnUsing(fn (PartSpecificationData $d) => $d);
        $command->shouldNotReceive('update');

        $details = ['front' => ['adapter_type_front' => ['A1', 'A2'], 'count_wipers' => 2]];

        $this->service($specs, $command)->upsertFromRow($this->wiperRow($details));

        $this->assertSame([['A1'], ['A2']], $createdAdapters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
