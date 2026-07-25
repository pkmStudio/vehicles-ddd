<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\KitCompositionValidator;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;
use Mockery;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class KitCompositionValidatorTest extends TestCase
{
    private TypeData $wiperType;

    private TypeData $adapterType;

    private TypeData $brakePadsType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wiperType = new TypeData(name: 'Щетки стеклоочистителя', char: 'WB', id: 3);
        $this->adapterType = new TypeData(name: 'Адаптер стеклоочистителя', char: 'AW', id: 7);
        $this->brakePadsType = new TypeData(name: 'Колодки', char: 'BP', id: 1);
    }

    public function test_allows_wipers_with_same_brand_and_category(): void
    {
        $validator = $this->validator();

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->wiper('WB-2', brandId: 10, category: 'FRAMELESS'),
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_rejects_wipers_with_different_categories(): void
    {
        $validator = $this->validator();

        $this->expectException(KitCompositionException::class);
        $this->expectExceptionMessage('Нельзя собрать комплект из разных категорий щеток: Бескаркасные, Зимние.');

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->wiper('WB-2', brandId: 10, category: 'WINTER'),
        ]));
    }

    public function test_rejects_wipers_with_different_brands(): void
    {
        $validator = $this->validator();

        $this->expectException(KitCompositionException::class);
        $this->expectExceptionMessage('Нельзя собрать комплект из разных брендов: 10, 20.');

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->wiper('WB-2', brandId: 20, category: 'FRAMELESS'),
        ]));
    }

    public function test_allows_wiper_plus_adapter_of_another_brand(): void
    {
        $validator = $this->validator();

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->adapter('AW-1', brandId: 20),
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_rejects_wiper_plus_adapter_plus_wiper_of_another_brand(): void
    {
        $validator = $this->validator();

        $this->expectException(KitCompositionException::class);
        $this->expectExceptionMessage('Нельзя собрать комплект из разных брендов: 10, 20.');

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->adapter('AW-1', brandId: 99),
            $this->wiper('WB-2', brandId: 20, category: 'FRAMELESS'),
        ]));
    }

    public function test_rejects_wiper_without_category(): void
    {
        $validator = $this->validator();

        $this->expectException(KitCompositionException::class);
        $this->expectExceptionMessage('У щетки WB-1 не заполнена категория.');

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: null),
        ]));
    }

    public function test_rejects_different_non_adapter_types(): void
    {
        $validator = $this->validator();

        $this->expectException(KitCompositionException::class);
        $this->expectExceptionMessage('Нельзя собрать комплект из разных типов товаров. Исключение: щетка + адаптер.');

        $validator->validate(new Collection([
            $this->wiper('WB-1', brandId: 10, category: 'FRAMELESS'),
            $this->brakePads('BP-1', brandId: 10),
        ]));
    }

    public function test_allows_single_non_wiper_type_with_same_brand(): void
    {
        $validator = $this->validator();

        $validator->validate(new Collection([
            $this->brakePads('BP-1', brandId: 10),
            $this->brakePads('BP-2', brandId: 10),
        ]));

        $this->addToAssertionCount(1);
    }

    private function validator(): KitCompositionValidator
    {
        $resolver = Mockery::mock(TypeTemplateResolverInterface::class);
        $resolver->shouldReceive('resolve')
            ->with($this->wiperType)
            ->andReturn(NomenclatureDetailTemplateEnum::WIPER)
            ->byDefault();
        $resolver->shouldReceive('resolve')
            ->with($this->adapterType)
            ->andReturn(NomenclatureDetailTemplateEnum::WIPER_ADAPTER)
            ->byDefault();
        $resolver->shouldReceive('resolve')
            ->with($this->brakePadsType)
            ->andReturn(NomenclatureDetailTemplateEnum::BRAKE_PADS)
            ->byDefault();

        return new KitCompositionValidator($resolver, new NullLogger);
    }

    private function wiper(string $partNumber, int $brandId, ?string $category): NomenclatureData
    {
        return $this->nomenclature(
            type: $this->wiperType,
            partNumber: $partNumber,
            brandId: $brandId,
            details: $category === null ? [] : ['category' => $category],
        );
    }

    private function adapter(string $partNumber, int $brandId): NomenclatureData
    {
        return $this->nomenclature(
            type: $this->adapterType,
            partNumber: $partNumber,
            brandId: $brandId,
        );
    }

    private function brakePads(string $partNumber, int $brandId): NomenclatureData
    {
        return $this->nomenclature(
            type: $this->brakePadsType,
            partNumber: $partNumber,
            brandId: $brandId,
        );
    }

    private function nomenclature(TypeData $type, string $partNumber, int $brandId, array $details = []): NomenclatureData
    {
        return new NomenclatureData(
            typeId: (int) $type->id,
            partNumber: $partNumber,
            quantityInPak: 1,
            quantityPak: 1,
            weight: 10,
            material: [],
            details: $details,
            type: $type,
            brandId: $brandId,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
