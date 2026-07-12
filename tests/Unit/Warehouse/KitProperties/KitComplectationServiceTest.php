<?php

declare(strict_types=1);

namespace Tests\Unit\Warehouse\KitProperties;

use App\Warehouse\KitProperties\Application\Services\KitComplectationService;
use Tests\TestCase;

final class KitComplectationServiceTest extends TestCase
{
    public function test_describes_quantity_with_single_material(): void
    {
        $service = new KitComplectationService;

        $this->assertSame(
            'В комплекте две щетки стеклоочистителя. Материал: Никель',
            $service->describe(2, 'Щетки стеклоочистителя', ['NICKEL']),
        );
    }

    public function test_describes_quantity_without_material(): void
    {
        $service = new KitComplectationService;

        $this->assertSame('В комплекте одна колодка', $service->describe(1, 'Колодки', []));
    }

    public function test_describes_quantity_with_multiple_materials(): void
    {
        $service = new KitComplectationService;

        $this->assertSame(
            'В комплекте один ШРУС. Материал: Никель, Сталь',
            $service->describe(1, 'ШРУС', ['NICKEL', 'STEEL']),
        );
    }

    public function test_returns_empty_string_for_unknown_type_name(): void
    {
        $service = new KitComplectationService;

        $this->assertSame('', $service->describe(1, 'Неизвестный тип', []));
    }
}
