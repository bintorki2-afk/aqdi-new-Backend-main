<?php

namespace App\Console\Commands;

use App\Services\Marketing\AdSpendSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncAdSpendCommand extends Command
{
    protected $signature = 'ads:sync-spend
        {--from= : Start date YYYY-MM-DD}
        {--to= : End date YYYY-MM-DD}
        {--days=3 : Lookback days when --from/--to are omitted}
        {--platform= : google|meta|tiktok|snapchat|twitter}';

    protected $description = 'Pull daily ad spend by campaign from configured advertising accounts';

    public function handle(AdSpendSyncService $sync): int
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        $days = max(1, (int) $this->option('days'));

        $to = is_string($toOption) && $toOption !== ''
            ? Carbon::parse($toOption)->endOfDay()
            : now()->endOfDay();
        $from = is_string($fromOption) && $fromOption !== ''
            ? Carbon::parse($fromOption)->startOfDay()
            : $to->copy()->subDays($days - 1)->startOfDay();

        $platform = $this->option('platform');
        $platform = is_string($platform) && $platform !== '' ? $platform : null;

        $results = $sync->sync($from, $to, $platform);

        $this->info('Spend window: '.$from->toDateString().' → '.$to->toDateString());
        $this->table(
            ['platform', 'synced', 'skipped', 'detail'],
            collect($results)->map(fn (array $row, string $key) => [
                $key,
                $row['synced'],
                $row['skipped'] ? 'yes' : 'no',
                $row['reason'] ?? (isset($row['missing']) ? implode(', ', $row['missing']) : ''),
            ])->values()->all()
        );

        return self::SUCCESS;
    }
}
