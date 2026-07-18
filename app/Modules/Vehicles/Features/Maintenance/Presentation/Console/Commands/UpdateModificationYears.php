<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Models\Modification;
use Illuminate\Console\Command;

class UpdateModificationYears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modifications:fix-years';

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
        $modifications = Modification::query()->where('year_to', '2025')->get();
        $this->info('Найдено модификаций: '.$modifications->count());
        $count = 0;

        foreach ($modifications as $modification) {
            $modification->update(['year_to' => null]);
            $count++;
        }
        $this->info('Исправлено записей: '.$count);
    }
}
