<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

/**
 * Копия для Export — без relation на Feature: Feature-модель здесь не дублируется
 * (Export её не читает, нужен только feature_value.name для листа дворников).
 */
class FeatureValue extends AbstractModel {}
