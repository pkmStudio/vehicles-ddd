<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Vehicle;

use Dan\FieldTemplates\AbstractTemplate;
use App\Vehicles\Domain\Templates\Vehicle\Templates\WiperTemplate;

final readonly class VehicleTemplateFactory
{
    public static function make(string $template): AbstractTemplate
    {
        return match ($template) {
            'wiper' => app(WiperTemplate::class),
            default => throw new \Exception("Неизвестный шаблон ТС: $template"),
        };
    }
}
