<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Models\Vehicle;
use Illuminate\Console\Command;

class UpdateVehicleYears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:fix-years';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправляет в year_to 2025 год на null';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vehicles = Vehicle::query()->where('generation_year_to', '2025')->get();
        $this->info('Найдено ТС: '.$vehicles->count());
        $count = 0;

        foreach ($vehicles as $vehicle) {
            $vehicle->update(['generation_year_to' => null]);
            $count++;
        }
        $this->info('Исправлено записей: '.$count);
    }
}
