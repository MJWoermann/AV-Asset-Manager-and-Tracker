<?php

namespace App\Support;

use App\Models\User;

class DashboardQuickLinks
{
    public const MAX_LINKS = 6;

    /**
     * @return list<array{id: string, label: string, url: string, eyebrow: string, description: string, primary: bool}>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $ids = self::orderedLinkIds($user);
        $links = [];

        foreach ($ids as $id) {
            $link = self::linkDefinition($id, $user);
            if ($link === null) {
                continue;
            }

            $links[] = $link;
            if (count($links) >= self::MAX_LINKS) {
                break;
            }
        }

        return $links;
    }

    /**
     * @return list<string>
     */
    private static function orderedLinkIds(User $user): array
    {
        if ($user->hasAnyRole(['admin', 'inventory_manager'])) {
            $ids = [
                'scan',
                'assets',
                'compare',
                'import',
                'maintenance',
                'spares',
                'data_hub',
                'consumables',
                'connection_map',
                'inbound_email',
                'labels',
                'rigging',
                'admin',
            ];
        } else {
            $ids = [
                'scan',
                'assets',
                'unknown_scans',
                'my_repairs',
                'consumables',
                'connection_map',
                'labels',
                'rigging',
            ];
        }

        return $ids;
    }

    /**
     * @return array{id: string, label: string, url: string, eyebrow: string, description: string, primary: bool}|null
     */
    private static function linkDefinition(string $id, User $user): ?array
    {
        return match ($id) {
            'scan' => [
                'id' => $id,
                'label' => 'Scan to event',
                'url' => route('scan.index'),
                'eyebrow' => 'Primary',
                'description' => 'Camera barcode scan with inventory compare',
                'primary' => true,
            ],
            'assets' => [
                'id' => $id,
                'label' => 'Assets',
                'url' => route('assets.index'),
                'eyebrow' => 'Inventory',
                'description' => 'Search and edit equipment',
                'primary' => false,
            ],
            'unknown_scans' => [
                'id' => $id,
                'label' => 'Unknown scans',
                'url' => route('reports.unknown-scans'),
                'eyebrow' => 'Reports',
                'description' => 'Unresolved barcode follow-ups',
                'primary' => false,
            ],
            'my_repairs' => [
                'id' => $id,
                'label' => 'My repairs',
                'url' => route('repairs.index', ['assignee' => 'me']),
                'eyebrow' => 'Maintenance',
                'description' => 'Repairs assigned to you',
                'primary' => false,
            ],
            'compare' => [
                'id' => $id,
                'label' => 'Compare lists',
                'url' => route('reports.compare'),
                'eyebrow' => 'Reports',
                'description' => 'Matched / missing / extras',
                'primary' => false,
            ],
            'import' => $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                'id' => $id,
                'label' => 'Import',
                'url' => route('import.create'),
                'eyebrow' => 'Data',
                'description' => 'Spreadsheet import wizard',
                'primary' => false,
            ] : null,
            'maintenance' => $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                'id' => $id,
                'label' => 'Maintenance',
                'url' => route('maintenance.index'),
                'eyebrow' => 'Hub',
                'description' => 'Repairs, spares, and procedures',
                'primary' => false,
            ] : null,
            'spares' => $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                'id' => $id,
                'label' => 'Spares',
                'url' => route('spares.index'),
                'eyebrow' => 'Register',
                'description' => 'Spare parts stock levels',
                'primary' => false,
            ] : null,
            'data_hub' => $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                'id' => $id,
                'label' => 'Data hub',
                'url' => route('data.index'),
                'eyebrow' => 'Catalogue',
                'description' => 'Locations, types, and files',
                'primary' => false,
            ] : null,
            'consumables' => StockTracking::enabledFor($user) ? [
                'id' => $id,
                'label' => 'Consumables',
                'url' => route('assets.index', ['stock_tracked' => 1]),
                'eyebrow' => 'Stock',
                'description' => 'Stock-tracked assets',
                'primary' => false,
            ] : null,
            'connection_map' => $user->hasFeature('asset_connections') ? [
                'id' => $id,
                'label' => 'Connection map',
                'url' => route('reports.connection-map'),
                'eyebrow' => 'Reports',
                'description' => 'Signal path overview',
                'primary' => false,
            ] : null,
            'inbound_email' => $user->hasFeature('email_integration')
                && $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                    'id' => $id,
                    'label' => 'Inbound email',
                    'url' => route('admin.email.inbound.index'),
                    'eyebrow' => 'Admin',
                    'description' => 'Pending email actions',
                    'primary' => false,
                ] : null,
            'labels' => $user->hasFeature('shelf_tag_generator')
                && $user->hasAnyRole(['admin', 'inventory_manager']) ? [
                    'id' => $id,
                    'label' => 'Labels',
                    'url' => route('labels.index'),
                    'eyebrow' => 'Print',
                    'description' => 'Shelf tag layouts',
                    'primary' => false,
                ] : null,
            'rigging' => $user->hasFeature('rigging') ? [
                'id' => $id,
                'label' => 'Rigging',
                'url' => route('rigging.events.index'),
                'eyebrow' => 'Events',
                'description' => 'Rig events and sign-off',
                'primary' => false,
            ] : null,
            'admin' => $user->hasRole('admin') ? [
                'id' => $id,
                'label' => 'Admin',
                'url' => route('admin.index'),
                'eyebrow' => 'Settings',
                'description' => 'Users and system settings',
                'primary' => false,
            ] : null,
            default => null,
        };
    }
}
