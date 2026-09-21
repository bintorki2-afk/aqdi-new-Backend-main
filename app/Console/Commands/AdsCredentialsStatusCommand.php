<?php

namespace App\Console\Commands;

use App\Services\Marketing\AdSpendSyncService;
use Illuminate\Console\Command;

class AdsCredentialsStatusCommand extends Command
{
    protected $signature = 'ads:credentials';

    protected $description = 'Show which advertising-account credentials are configured for marketing reports';

    public function handle(AdSpendSyncService $sync): int
    {
        $rows = array_map(static fn (array $row) => [
            $row['platform'],
            $row['label'],
            $row['configured'] ? 'yes' : 'no',
            $row['missing'] === [] ? '-' : implode(', ', $row['missing']),
        ], $sync->credentialStatus());

        $this->table(['platform', 'label', 'configured', 'missing'], $rows);
        $this->line('WhatsApp paid ads are read from the Meta ad account (utm_source=meta). Organic WhatsApp uses utm_source=whatsapp and has no spend.');
        $this->line('X Ads without a bearer token: import CSV/JSON via POST /api/admin/reports/marketing/spend.');

        return self::SUCCESS;
    }
}
