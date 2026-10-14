<?php

namespace App\Services;

use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Models\Asset;
use App\Models\NotificationTemplate;
use App\Models\OutboundEmailThread;
use App\Models\User;
use App\Services\Mail\MailTransportManager;
use App\Support\EmailIntegrationSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationDispatchService
{
    public function __construct(
        private NotificationFieldCatalog $catalog,
        private MailTransportManager $transports,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{sent: bool, warning: string|null, thread: OutboundEmailThread|null}
     */
    public function dispatch(string|NotificationEventKey $eventKey, Asset $asset, ?User $actor = null, array $context = []): array
    {
        $event = $eventKey instanceof NotificationEventKey
            ? $eventKey->value
            : (string) $eventKey;

        if (! EmailIntegrationSettings::isEnabled()) {
            return ['sent' => false, 'warning' => null, 'thread' => null];
        }

        $template = NotificationTemplate::query()
            ->where('event_key', $event)
            ->where('is_active', true)
            ->with(['recipients' => fn ($query) => $query->where('is_active', true)])
            ->first();

        if (! $template || $template->recipients->isEmpty()) {
            return ['sent' => false, 'warning' => 'No active template/recipients for '.$event.'.', 'thread' => null];
        }

        $selectedFields = is_array($template->selected_fields) ? $template->selected_fields : [];
        $values = $this->catalog->resolve($asset, $actor, null);

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $values[(string) $key] = (string) ($value ?? '');
            }
        }

        $eligibleRecipients = [];
        $warnings = [];

        foreach ($template->recipients as $recipient) {
            $required = is_array($recipient->pivot->required_fields)
                ? $recipient->pivot->required_fields
                : [];

            $missing = $this->missingRequiredFields($required, $values, $event);
            if ($missing !== []) {
                $warnings[] = $recipient->email.' missing: '.implode(', ', $missing);

                continue;
            }

            $eligibleRecipients[] = $recipient;
        }

        if ($eligibleRecipients === []) {
            return [
                'sent' => false,
                'warning' => 'Notification not sent — required fields empty ('.implode('; ', $warnings).').',
                'thread' => null,
            ];
        }

        $renderValues = $selectedFields === []
            ? $values
            : collect($selectedFields)
                ->mapWithKeys(fn (string $key): array => [$key => (string) ($values[$key] ?? '')])
                ->merge(collect($values)->only(['user.name', 'asset.url']))
                ->all();

        // Allow any resolved key in render so body placeholders work.
        $renderValues = array_merge($values, $renderValues);

        $subject = $this->catalog->render($template->subject, $renderValues);
        $body = nl2br(e($this->catalog->render($template->body, $renderValues)));
        $to = collect($eligibleRecipients)->pluck('email')->unique()->values()->all();

        $settings = EmailIntegrationSettings::get();

        try {
            $result = $this->transports->driver()->send(
                $to,
                $subject,
                $body,
                $settings['from_address'],
                $settings['from_name'],
            );
        } catch (Throwable $e) {
            Log::warning('Notification dispatch failed', [
                'event' => $event,
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'warning' => 'Notification send failed: '.$e->getMessage(),
                'thread' => null,
            ];
        }

        $thread = OutboundEmailThread::query()->create([
            'asset_id' => $asset->id,
            'event_key' => $event,
            'provider_message_id' => $result['provider_message_id'],
            'conversation_id' => $result['conversation_id'],
            'subject' => $subject,
            'to_addresses' => $to,
            'sent_by' => $actor?->id,
        ]);

        if ($event === NotificationEventKey::AssetMacOnboarding->value) {
            app(AssetMaintenanceLogService::class)->create($asset, [
                'type' => MaintenanceLogType::Note,
                'summary' => 'MAC onboarding email sent to '.implode(', ', $to),
                'body' => 'Outbound notification for IT helpdesk MAC registration.',
                'source' => 'email_outbound',
            ], $actor);
        }

        return [
            'sent' => true,
            'warning' => $warnings === [] ? null : 'Some recipients skipped: '.implode('; ', $warnings),
            'thread' => $thread,
        ];
    }

    /**
     * MAC-bearing required fields are satisfied when any one has a value.
     *
     * @param  list<string>  $requiredFields
     * @param  array<string, string>  $values
     * @return list<string>
     */
    protected function missingRequiredFields(array $requiredFields, array $values, string $event): array
    {
        $macKeys = [];
        $otherKeys = [];

        foreach ($requiredFields as $key) {
            if ($key === 'asset.mac_address' || ($event === NotificationEventKey::AssetMacOnboarding->value && str_starts_with($key, 'custom.') && str_contains(strtolower($key), 'mac'))) {
                $macKeys[] = $key;
            } else {
                $otherKeys[] = $key;
            }
        }

        $missing = $this->catalog->missingRequiredFields($otherKeys, $values);

        if ($macKeys !== []) {
            $hasMac = collect($macKeys)->contains(
                fn (string $key): bool => trim((string) ($values[$key] ?? '')) !== ''
            );

            if (! $hasMac) {
                $missing[] = 'one of: '.implode(', ', $macKeys);
            }
        }

        return $missing;
    }
}
