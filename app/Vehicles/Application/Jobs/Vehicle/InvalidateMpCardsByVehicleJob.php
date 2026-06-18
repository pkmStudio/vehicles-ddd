<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Jobs\Vehicle;

use App\Jobs\MpSale\MpCard\Maintenance\ResolveDirtyCardsJob;
use App\Models\MpSale\MpCard;
use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class InvalidateMpCardsByVehicleJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(private readonly int $vehicleId) {}

    public function uniqueId(): string
    {
        return (string) $this->vehicleId;
    }

    public function handle(): void
    {
        $vehicleAppIds = DB::table('kit_applicabilitables')
            ->where('applicabilitable_type', Vehicle::class)
            ->where('applicabilitable_id', $this->vehicleId)
            ->pluck('id');

        $modIds = DB::table('modifications')
            ->where('vehicle_id', $this->vehicleId)
            ->pluck('id');

        $modAppIds = DB::table('kit_applicabilitables')
            ->where('applicabilitable_type', Modification::class)
            ->whereIn('applicabilitable_id', $modIds)
            ->pluck('id');

        $allAppIds = $vehicleAppIds->merge($modAppIds)->unique();

        if ($allAppIds->isNotEmpty()) {
            $cardIds = MpCard::query()
                ->whereIn('kit_applicabilitable_id', $allAppIds)
                ->pluck('id')
                ->all();

            if ($cardIds !== []) {
                ResolveDirtyCardsJob::dispatch($cardIds);
            }
        }
    }
}
