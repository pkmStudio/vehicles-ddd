<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Engine;

use Dan\FieldTemplates\AbstractTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\AirFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\OilFilterTemplate;
use App\Vehicles\Domain\Templates\Engine\Templates\SparkPlugTemplate;

final readonly class EngineTemplateFactory
{
    public static function make(string $template): AbstractTemplate
    {
        return match ($template) {
            'sparkPlugs' => app(SparkPlugTemplate::class),
            'oilFilter' => app(OilFilterTemplate::class),
            'airFilter' => app(AirFilterTemplate::class),
            default => throw new \Exception("Неизвестный шаблон двигателя: $template"),
        };
    }
}
