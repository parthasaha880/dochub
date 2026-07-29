<?php

namespace App\Modules\Retention\Console;

use App\Modules\Retention\Services\RetentionService;
use Illuminate\Console\Command;

class ProcessRetentionCommand extends Command
{
    protected $signature = 'retention:process {--organization= : Optional organization UUID}';

    protected $description = 'Apply active retention policies to eligible documents';

    public function handle(RetentionService $service): int
    {
        $run = $service->process($this->option('organization') ?: null);

        $this->info("Retention run {$run->id}: processed={$run->processed} archived={$run->archived} deleted={$run->soft_deleted} flagged={$run->flagged}");

        return self::SUCCESS;
    }
}
