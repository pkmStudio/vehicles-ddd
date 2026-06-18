<?php

declare(strict_types=1);

namespace App\Vehicles\Jobs\Engine;

use App\Jobs\MpSale\MpCard\Maintenance\ResolveDirtyCardsJob;
use App\Models\MpSale\MpCard;
use App\Vehicles\Models\Engine;
use App\Vehicles\Models\PartSpecification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class InvalidateMpCardsByEngineJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(private readonly int $engineId) {}

    public function uniqueId(): string
    {
        return (string) $this->engineId;
    }

    public function handle(): void
    {
        $partSpecIds = DB::table('part_specifications')
            ->where('partable_type', Engine::class)
            ->where('partable_id', $this->engineId)
            ->pluck('id');

        if ($partSpecIds->isEmpty()) {
            return;
        }

        $appIds = DB::table('kit_applicabilitables')
            ->where('applicabilitable_type', PartSpecification::class)
            ->whereIn('applicabilitable_id', $partSpecIds)
            ->pluck('id');

        if ($appIds->isNotEmpty()) {
            $cardIds = MpCard::query()
                ->whereIn('kit_applicabilitable_id', $appIds)
                ->pluck('id')
                ->all();

            if ($cardIds !== []) {
                ResolveDirtyCardsJob::dispatch($cardIds);
            }
        }
    }
}
