<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Enums\MaintenanceLogType;
use App\Http\Requests\StoreAssetMaintenanceLogRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Services\AssetMaintenanceLogService;
use App\Services\FileAttachmentService;
use App\Services\RepairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class AssetMaintenanceLogController extends Controller
{
    public function store(
        StoreAssetMaintenanceLogRequest $request,
        Asset $asset,
        AssetMaintenanceLogService $maintenanceLogs,
        RepairService $repairs,
        FileAttachmentService $attachments,
    ): RedirectResponse {
        $payload = $request->safe()->only(['type', 'summary', 'body', 'occurred_at', 'status']);
        $type = $payload['type'] instanceof MaintenanceLogType
            ? $payload['type']
            : MaintenanceLogType::from((string) $payload['type']);

        $startRepair = $request->boolean('start_repair_now') && $type === MaintenanceLogType::Fault;

        if ($startRepair) {
            $repair = $repairs->startWithNewFault($asset, [
                'summary' => $payload['summary'],
                'body' => $payload['body'] ?? null,
                'status' => $payload['status'] ?? null,
                'repair_procedure_id' => $request->validated('repair_procedure_id'),
                'assignee_user_id' => $request->validated('assignee_user_id'),
            ], $request->user());

            $fault = $repair->maintenanceLog;
            $uploads = $request->file('files', []);
            if ($fault && is_array($uploads)) {
                foreach ($uploads as $upload) {
                    if ($upload instanceof UploadedFile) {
                        $attachments->storeUpload($upload, [
                            'title' => pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: ('Maintenance '.$fault->id),
                            'category' => FileCategory::Maintenance->value,
                        ], $fault);
                    }
                }
            }

            return redirect()
                ->route('repairs.show', $repair)
                ->with('status', 'Fault logged and repair started.');
        }

        $maintenanceLogs->create(
            $asset,
            $payload,
            $request->user(),
            $request->file('files'),
        );

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'maintenance'])
            ->with('status', 'Maintenance entry logged.');
    }

    public function destroy(Asset $asset, AssetMaintenanceLog $maintenanceLog): RedirectResponse
    {
        abort_unless((int) $maintenanceLog->asset_id === (int) $asset->id, 404);

        $maintenanceLog->delete();

        return redirect()
            ->route('assets.show', [$asset, 'tab' => 'maintenance'])
            ->with('status', 'Maintenance entry removed.');
    }
}
