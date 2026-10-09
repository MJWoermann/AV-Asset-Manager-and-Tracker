<?php

namespace App\Services;

use App\Models\AssetList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListComparisonService
{
    /**
     * Compare scanned/event list against inventory list.
     *
     * @return array{matched: Collection, inventory_only: Collection, scanned_only: Collection}
     */
    public function compare(AssetList $scannedList, AssetList $inventoryList): array
    {
        $scanned = DB::table('asset_list_items as s')
            ->join('assets as a', 'a.id', '=', 's.asset_id')
            ->where('s.asset_list_id', $scannedList->id)
            ->select('a.id as asset_id', 'a.name', 'a.fmi_ast', 'a.tp_barcode', 'a.rig_tag', 'a.serial_number', 's.quantity', 's.is_child_expand', 'a.parent_id')
            ->get()
            ->keyBy('asset_id');

        $inventory = DB::table('asset_list_items as i')
            ->join('assets as a', 'a.id', '=', 'i.asset_id')
            ->where('i.asset_list_id', $inventoryList->id)
            ->select('a.id as asset_id', 'a.name', 'a.fmi_ast', 'a.tp_barcode', 'a.rig_tag', 'a.serial_number', 'i.quantity', 'a.parent_id')
            ->get()
            ->keyBy('asset_id');

        $matched = collect();
        $inventoryOnly = collect();
        $scannedOnly = collect();

        foreach ($inventory as $assetId => $row) {
            if ($scanned->has($assetId)) {
                $matched->push((object) [
                    'asset_id' => $assetId,
                    'name' => $row->name,
                    'fmi_ast' => $row->fmi_ast,
                    'tp_barcode' => $row->tp_barcode,
                    'rig_tag' => $row->rig_tag,
                    'serial_number' => $row->serial_number,
                    'inventory_qty' => $row->quantity,
                    'scanned_qty' => $scanned[$assetId]->quantity,
                    'parent_id' => $row->parent_id,
                ]);
            } else {
                $inventoryOnly->push($row);
            }
        }

        foreach ($scanned as $assetId => $row) {
            if (! $inventory->has($assetId)) {
                $scannedOnly->push($row);
            }
        }

        return [
            'matched' => $matched->values(),
            'inventory_only' => $inventoryOnly->values(),
            'scanned_only' => $scannedOnly->values(),
        ];
    }
}
