<?php

namespace App\Services;

use App\Enums\InboundEmailActionStatus;
use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Models\Asset;
use App\Models\InboundEmailAction;
use App\Models\InboundEmailRule;
use App\Models\OutboundEmailThread;
use App\Models\User;
use App\Services\Mail\MailTransportManager;
use App\Support\EmailIntegrationSettings;
use App\Support\MacAddress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class InboundEmailService
{
    public const MAX_BODY_LENGTH = 10000;

    public function __construct(
        private MailTransportManager $transports,
        private AssetMaintenanceLogService $maintenanceLogs,
        private NotificationDispatchService $notifications,
    ) {}

    public function poll(): int
    {
        if (! EmailIntegrationSettings::isEnabled()) {
            return 0;
        }

        try {
            $messages = $this->transports->driver()->fetchUnread();
        } catch (Throwable $e) {
            Log::warning('Inbound email poll failed', ['error' => $e->getMessage()]);

            return 0;
        }

        $processed = 0;
        foreach ($messages as $message) {
            if ($this->processMessage($message)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * @param  array{
     *     id: string,
     *     subject: string|null,
     *     from: string|null,
     *     body: string,
     *     snippet: string|null,
     *     in_reply_to: string|null,
     *     references: list<string>,
     *     conversation_id: string|null,
     *     headers: array<string, mixed>
     * }  $message
     */
    public function processMessage(array $message): bool
    {
        $sourceId = (string) ($message['id'] ?? '');
        if ($sourceId === '') {
            return false;
        }

        if (InboundEmailAction::query()->where('source_message_id', $sourceId)->exists()) {
            return false;
        }

        $thread = $this->matchThread($message);
        $body = $this->sanitizeBody((string) ($message['body'] ?? ''));

        if ($thread) {
            return $this->autoApplyThreadReply($message, $thread, $body);
        }

        $rule = $this->matchRule($message);
        $asset = $this->resolveAssetFromMessage($message, $body);

        $payload = [
            'type' => ($rule?->log_type?->value) ?? MaintenanceLogType::Note->value,
            'summary' => 'Email: '.Str::limit((string) ($message['subject'] ?: 'Inbound message'), 200),
            'body' => $body,
            'status' => $rule?->fault_status,
            'source' => 'email',
        ];

        $action = InboundEmailAction::query()->create([
            'status' => InboundEmailActionStatus::Pending,
            'source_message_id' => $sourceId,
            'from_address' => $message['from'] ?? null,
            'subject' => $message['subject'] ?? null,
            'snippet' => Str::limit((string) ($message['snippet'] ?? $body), 280),
            'raw_headers' => $message['headers'] ?? [],
            'inbound_email_rule_id' => $rule?->id,
            'asset_id' => $asset?->id,
            'proposed_payload' => $payload,
        ]);

        if ($rule && ! $rule->require_review && $asset) {
            $this->applyAction($action);

            return true;
        }

        if ($asset) {
            $this->notifications->dispatch(
                NotificationEventKey::InboundMatchPending,
                $asset,
                null,
                ['inbound.subject' => (string) ($message['subject'] ?? '')]
            );
        }

        $this->markMessageProcessed($message);

        return true;
    }

    public function applyAction(InboundEmailAction $action, ?User $user = null): InboundEmailAction
    {
        $action->loadMissing(['asset', 'rule']);

        if (! $action->asset) {
            $action->update([
                'status' => InboundEmailActionStatus::Failed,
                'processed_by' => $user?->id,
                'processed_at' => now(),
            ]);

            return $action->fresh();
        }

        $payload = is_array($action->proposed_payload) ? $action->proposed_payload : [];

        $log = $this->maintenanceLogs->create($action->asset, [
            'type' => $payload['type'] ?? MaintenanceLogType::Note->value,
            'summary' => (string) ($payload['summary'] ?? 'Inbound email'),
            'body' => $payload['body'] ?? null,
            'status' => $payload['status'] ?? null,
            'source' => 'email',
        ], $user);

        $action->update([
            'status' => InboundEmailActionStatus::Applied,
            'asset_maintenance_log_id' => $log->id,
            'processed_by' => $user?->id,
            'processed_at' => now(),
        ]);

        $this->notifications->dispatch(
            NotificationEventKey::InboundMatchApplied,
            $action->asset,
            $user,
        );

        return $action->fresh(['maintenanceLog', 'asset']);
    }

    public function rejectAction(InboundEmailAction $action, ?User $user = null): InboundEmailAction
    {
        $action->update([
            'status' => InboundEmailActionStatus::Rejected,
            'processed_by' => $user?->id,
            'processed_at' => now(),
        ]);

        if ($action->asset) {
            $this->notifications->dispatch(
                NotificationEventKey::InboundMatchRejected,
                $action->asset,
                $user,
            );
        }

        return $action->fresh();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function autoApplyThreadReply(array $message, OutboundEmailThread $thread, string $body): bool
    {
        $from = (string) ($message['from'] ?? 'unknown');
        $log = $this->maintenanceLogs->create($thread->asset, [
            'type' => MaintenanceLogType::Note,
            'summary' => 'Email reply from '.$from,
            'body' => $body,
            'source' => 'email',
        ]);

        InboundEmailAction::query()->create([
            'status' => InboundEmailActionStatus::AutoApplied,
            'source_message_id' => (string) $message['id'],
            'from_address' => $message['from'] ?? null,
            'subject' => $message['subject'] ?? null,
            'snippet' => Str::limit($body, 280),
            'raw_headers' => $message['headers'] ?? [],
            'outbound_email_thread_id' => $thread->id,
            'asset_id' => $thread->asset_id,
            'asset_maintenance_log_id' => $log->id,
            'proposed_payload' => [
                'type' => MaintenanceLogType::Note->value,
                'summary' => 'Email reply from '.$from,
                'body' => $body,
                'source' => 'email',
            ],
            'processed_at' => now(),
        ]);

        $this->markMessageProcessed($message);

        return true;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function markMessageProcessed(array $message): void
    {
        $markId = (string) (data_get($message, 'headers.graph_id') ?: ($message['id'] ?? ''));
        if ($markId === '') {
            return;
        }

        try {
            $this->transports->driver()->markProcessed($markId);
        } catch (Throwable) {
            // ignore mark failures
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function matchThread(array $message): ?OutboundEmailThread
    {
        $candidates = collect([
            $message['in_reply_to'] ?? null,
            ...($message['references'] ?? []),
        ])->filter()->map(fn ($id) => trim((string) $id))->unique()->values();

        if ($candidates->isNotEmpty()) {
            $thread = OutboundEmailThread::query()
                ->whereIn('provider_message_id', $candidates->all())
                ->first();
            if ($thread) {
                return $thread;
            }
        }

        $conversationId = $message['conversation_id'] ?? null;
        if (filled($conversationId)) {
            return OutboundEmailThread::query()
                ->where('conversation_id', $conversationId)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function matchRule(array $message): ?InboundEmailRule
    {
        $rules = InboundEmailRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $message)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function ruleMatches(InboundEmailRule $rule, array $message): bool
    {
        $conditions = is_array($rule->match_conditions) ? $rule->match_conditions : [];
        if ($conditions === []) {
            return false;
        }

        $subject = strtolower((string) ($message['subject'] ?? ''));
        $from = strtolower((string) ($message['from'] ?? ''));
        $body = strtolower((string) ($message['body'] ?? ''));

        if (isset($conditions['subject_contains']) && is_string($conditions['subject_contains'])) {
            if (! str_contains($subject, strtolower($conditions['subject_contains']))) {
                return false;
            }
        }

        if (isset($conditions['from_contains']) && is_string($conditions['from_contains'])) {
            if (! str_contains($from, strtolower($conditions['from_contains']))) {
                return false;
            }
        }

        if (isset($conditions['body_contains']) && is_string($conditions['body_contains'])) {
            if (! str_contains($body, strtolower($conditions['body_contains']))) {
                return false;
            }
        }

        return isset($conditions['subject_contains'])
            || isset($conditions['from_contains'])
            || isset($conditions['body_contains']);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function resolveAssetFromMessage(array $message, string $body): ?Asset
    {
        $haystack = implode(' ', [
            (string) ($message['subject'] ?? ''),
            $body,
        ]);

        foreach (['fmi_ast', 'tp_barcode', 'rig_tag', 'serial_number', 'mac_address'] as $column) {
            if (preg_match('/\b('.preg_quote($column, '/').'|'.str_replace('_', ' ?', $column).')\s*[:=]\s*([A-Za-z0-9:._-]+)/i', $haystack, $matches)) {
                if ($column === 'mac_address') {
                    $query = Asset::query();
                    MacAddress::whereEquals($query, $matches[2], 'mac_address');
                    $asset = $query->first();
                } else {
                    $asset = Asset::query()->where($column, $matches[2])->first();
                }
                if ($asset) {
                    return $asset;
                }
            }
        }

        // Bare identifier tokens that match indexed columns exactly.
        if (preg_match_all('/\b([A-Za-z0-9][A-Za-z0-9:._-]{2,})\b/', $haystack, $matches)) {
            foreach (array_unique($matches[1]) as $token) {
                $asset = Asset::query()->findByIdentifier($token)->first();
                if ($asset) {
                    return $asset;
                }
            }
        }

        return null;
    }

    protected function sanitizeBody(string $body): string
    {
        $plain = trim(html_entity_decode(strip_tags($body)));

        return Str::limit($plain, self::MAX_BODY_LENGTH, '…');
    }
}
