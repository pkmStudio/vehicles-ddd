<?php

use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCatalogController;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCrmController;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers\NomenclatureCrmController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('catalog')->group(function (): void {
        Route::get('manufacturers', [VehicleCatalogController::class, 'manufacturers']);
        Route::get('manufacturers/{manufacturer}/vehicles', [VehicleCatalogController::class, 'vehicles'])
            ->whereNumber('manufacturer');
        Route::get('vehicles/{vehicle}/modifications', [VehicleCatalogController::class, 'modifications'])
            ->whereNumber('vehicle');
        Route::get('modifications/{modification}', [VehicleCatalogController::class, 'showModification'])
            ->whereNumber('modification');
    });

    Route::get('vehicles/options/features', [VehicleCrmController::class, 'features']);
    Route::get('vehicles/options/feature-values', [VehicleCrmController::class, 'featureValues']);
    Route::get('vehicles/options/detail-templates', [VehicleCrmController::class, 'detailTemplates']);
    Route::get('vehicles/options/manufacturers', [VehicleCrmController::class, 'manufacturers']);
    Route::get('vehicles/search', [VehicleCrmController::class, 'search']);
    Route::get('vehicles/{id}', [VehicleCrmController::class, 'show'])->whereNumber('id');
    Route::get('vehicles', [VehicleCrmController::class, 'index']);

    Route::get('warehouse/nomenclatures/options/types', [NomenclatureCrmController::class, 'types']);
    Route::get('warehouse/nomenclatures/options/brands', [NomenclatureCrmController::class, 'brands']);
    Route::get('warehouse/nomenclatures/search', [NomenclatureCrmController::class, 'search']);
    Route::get('warehouse/nomenclatures/{id}', [NomenclatureCrmController::class, 'show'])->whereNumber('id');
    Route::get('warehouse/nomenclatures', [NomenclatureCrmController::class, 'index']);
});
