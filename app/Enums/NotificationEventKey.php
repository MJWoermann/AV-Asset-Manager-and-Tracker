<?php

namespace App\Enums;

enum NotificationEventKey: string
{
    case AssetMacOnboarding = 'asset_mac_onboarding';
    case MaintenanceLogCreated = 'maintenance_log_created';
    case MaintenanceFaultStatusChanged = 'maintenance_fault_status_changed';
    case InboundMatchPending = 'inbound_match_pending';
    case InboundMatchApplied = 'inbound_match_applied';
    case InboundMatchRejected = 'inbound_match_rejected';

    public function label(): string
    {
        return match ($this) {
            self::AssetMacOnboarding => 'MAC onboarding',
            self::MaintenanceLogCreated => 'Maintenance log created',
            self::MaintenanceFaultStatusChanged => 'Fault status changed',
            self::InboundMatchPending => 'Inbound match pending',
            self::InboundMatchApplied => 'Inbound match applied',
            self::InboundMatchRejected => 'Inbound match rejected',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
