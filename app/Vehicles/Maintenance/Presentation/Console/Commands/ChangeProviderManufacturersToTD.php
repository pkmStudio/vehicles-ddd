<?php

declare(strict_types=1);

namespace App\Vehicles\Maintenance\Presentation\Console\Commands;

use App\Vehicles\Maintenance\Infrastructure\Models\Manufacturer;
use Illuminate\Console\Command;

class ChangeProviderManufacturersToTD extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-provider-manufacturers-to-TD';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Меняет поставщика информации с OD на TD';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $manufacturers = Manufacturer::query()
            ->where('mfa_id','>', 0)
            ->where('provider', 'OD')
            ->get();
        $this->info('Найдено записей: '.$manufacturers->count());
        $count = 0;
        foreach ($manufacturers as $manufacturer) {
            $manufacturer->update(['provider' => 'TD']);
            $count++;
        }

        $this->info('Исправлено записей: '.$count);
    }
}
