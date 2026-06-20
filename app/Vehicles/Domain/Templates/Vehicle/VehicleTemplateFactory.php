<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Templates\Vehicle;

use App\Vehicles\Domain\Templates\Vehicle\Templates\WiperTemplate;
use Dan\FieldTemplates\AbstractTemplate;

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
