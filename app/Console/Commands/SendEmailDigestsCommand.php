<?php

namespace App\Console\Commands;

use App\Services\EmailDigestService;
use App\Support\EmailIntegrationSettings;
use Illuminate\Console\Command;

class SendEmailDigestsCommand extends Command
{
    protected $signature = 'email:send-digests';

    protected $description = 'Send enabled warranty, test & tag, and low-stock email digests';

    public function handle(EmailDigestService $digests): int
    {
        if (! EmailIntegrationSettings::isEnabled()) {
            $this->info('Email integration is disabled.');

            return self::SUCCESS;
        }

        $result = $digests->sendEnabledDigests();

        $this->info("Digest run complete: {$result['sent']} sent, {$result['skipped']} skipped.");

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
