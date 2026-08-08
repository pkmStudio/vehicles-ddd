<?php

use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCatalogController;
use App\Modules\Vehicles\Features\Catalog\Presentation\Http\Controllers\VehicleCrmController;
use App\Modules\Warehouse\Features\Catalog\Presentation\Http\Controllers\NomenclatureCatalogController;
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
        Route::get('categories', [NomenclatureCatalogController::class, 'categories']);
        Route::get('categories/{category}/nomenclatures', [NomenclatureCatalogController::class, 'nomenclatures'])
            ->whereNumber('category');
        Route::get('search', [NomenclatureCatalogController::class, 'search']);
        Route::get('nomenclatures/{partNumber}', [NomenclatureCatalogController::class, 'show'])
            ->where('partNumber', '.*');
    });

    Route::prefix('crm')->group(function (): void {
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
    });
});
