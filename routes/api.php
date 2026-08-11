<?php

use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCatalogController;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCrmController;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers\KitCrmController;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers\NomenclatureCrmController;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers\PackDimensionCrmController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('catalog')
        ->middleware('service.key:services.dan_catalog.read_api_key')
        ->group(function (): void {
            Route::get('manufacturers', [VehicleCatalogController::class, 'manufacturers']);
            Route::get('manufacturers/{manufacturer}/vehicles', [VehicleCatalogController::class, 'vehicles'])
                ->whereNumber('manufacturer');
            Route::get('vehicles/{vehicle}/modifications', [VehicleCatalogController::class, 'modifications'])
                ->whereNumber('vehicle');
            Route::get('modifications/{modification}', [VehicleCatalogController::class, 'showModification'])
                ->whereNumber('modification');
        });

    Route::prefix('crm')
        ->middleware('service.key:services.dan_vehicles.read_api_key')
        ->group(function (): void {
            Route::prefix('vehicles')->group(function (): void {
                Route::get('options/features', [VehicleCrmController::class, 'features']);
                Route::get('options/feature-values', [VehicleCrmController::class, 'featureValues']);
                Route::get('options/detail-templates', [VehicleCrmController::class, 'detailTemplates']);
                Route::get('options/manufacturers', [VehicleCrmController::class, 'manufacturers']);
                Route::get('search', [VehicleCrmController::class, 'search']);
                Route::get('{id}', [VehicleCrmController::class, 'show'])->whereNumber('id');
                Route::get('/', [VehicleCrmController::class, 'index']);
            });

            Route::prefix('warehouse/nomenclatures')->group(function (): void {
                Route::get('options/types', [NomenclatureCrmController::class, 'types']);
                Route::get('options/brands', [NomenclatureCrmController::class, 'brands']);
                Route::get('search', [NomenclatureCrmController::class, 'search']);
                Route::get('{id}', [NomenclatureCrmController::class, 'show'])->whereNumber('id');
                Route::get('/', [NomenclatureCrmController::class, 'index']);
            });

            Route::prefix('warehouse/kits')->group(function (): void {
                Route::get('options/nomenclatures', [KitCrmController::class, 'nomenclatures']);
                Route::get('options/pack-dimensions', [KitCrmController::class, 'packDimensions']);
                Route::get('options/types', [KitCrmController::class, 'types']);
                Route::get('{id}', [KitCrmController::class, 'show'])->whereNumber('id');
                Route::get('/', [KitCrmController::class, 'index']);
            });

            Route::prefix('warehouse/pack-dimensions')->group(function (): void {
                Route::get('options/types', [PackDimensionCrmController::class, 'types']);
                Route::get('{id}', [PackDimensionCrmController::class, 'show'])->whereNumber('id');
                Route::get('/', [PackDimensionCrmController::class, 'index']);
            });
        });
});
