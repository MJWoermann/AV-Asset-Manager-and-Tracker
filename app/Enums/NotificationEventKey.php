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
    case RepairStarted = 'repair_started';
    case RepairCompleted = 'repair_completed';
    case WarrantyExpiryDigest = 'warranty_expiry_digest';
    case TestTagExpiryDigest = 'test_tag_expiry_digest';
    case LowStockDigest = 'low_stock_digest';

    public function label(): string
    {
        return match ($this) {
            self::AssetMacOnboarding => 'MAC onboarding',
            self::MaintenanceLogCreated => 'Maintenance log created',
            self::MaintenanceFaultStatusChanged => 'Fault status changed',
            self::InboundMatchPending => 'Inbound match pending',
            self::InboundMatchApplied => 'Inbound match applied',
            self::InboundMatchRejected => 'Inbound match rejected',
            self::RepairStarted => 'Repair started',
            self::RepairCompleted => 'Repair completed',
            self::WarrantyExpiryDigest => 'Warranty expiry digest',
            self::TestTagExpiryDigest => 'Test & Tag expiry digest',
            self::LowStockDigest => 'Low stock digest',
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
