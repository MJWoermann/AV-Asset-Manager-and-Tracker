<?php

namespace App\Console\Commands;

use App\Services\InboundEmailService;
use App\Support\EmailIntegrationSettings;
use Illuminate\Console\Command;

class PollInboundEmailCommand extends Command
{
    protected $signature = 'email:poll-inbound';

    protected $description = 'Poll the configured mailbox for inbound email matching rules and helpdesk replies';

    public function handle(InboundEmailService $inbound): int
    {
        if (! EmailIntegrationSettings::isEnabled()) {
            $this->info('Email integration is disabled.');

            return self::SUCCESS;
        }

        $count = $inbound->poll();
        $this->info("Processed {$count} inbound message(s).");

        return self::SUCCESS;
    }
}
