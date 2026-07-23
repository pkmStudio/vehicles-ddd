<?php

declare(strict_types=1);

namespace Tests\Concerns\Applicability;

use Illuminate\Support\Facades\DB;

trait CreatesWarehouseKit
{
    protected function createWarehouseKit(): int
    {
        $typeId = DB::table('types')->insertGetId([
            'name' => 'ЩЕТКИ СТЕКЛООЧИСТИТЕЛЯ',
            'char' => 'WB',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packDimensionId = DB::table('pack_dimensions')->insertGetId([
            'name' => 'Box',
            'weight' => 1,
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'price' => 1,
            'generated' => false,
            'type_id' => $typeId,
        ]);

        return DB::table('kits')->insertGetId([
            'complectation' => 'Kit',
            'guarantee' => 12,
            'quantity_in_package' => 1,
            'quantity_package' => 1,
            'complement' => true,
            'weight' => 1,
            'is_sale_separately' => false,
            'is_active' => true,
            'pack_dimension_id' => $packDimensionId,
            'type_id' => $typeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
