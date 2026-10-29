<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\ScanSession;
use App\Services\ScanService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Scan a barcode/code into the active (or specified) scan session via ScanService.')]
class ScanCodeTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private ScanService $scanService) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'scan');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'scan_session_id' => ['nullable', 'integer', 'exists:scan_sessions,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $session = null;
        if (! empty($data['scan_session_id'])) {
            $session = ScanSession::query()->findOrFail((int) $data['scan_session_id']);
            if ((int) $session->user_id !== (int) $user->id && ! $user->hasRole('admin')) {
                return Response::error('Scan session does not belong to you.');
            }
        } else {
            $session = ScanSession::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        if ($session === null) {
            return Response::error('No active scan session. Call start_scan_session first.');
        }

        $result = $this->scanService->scanCode(
            $session,
            trim($data['code']),
            (int) ($data['quantity'] ?? 1),
        );

        return $this->jsonResult([
            'scan_session_id' => $session->id,
            'message' => $result['message'] ?? null,
            'duplicate' => (bool) ($result['duplicate'] ?? false),
            'unknown' => (bool) ($result['unknown'] ?? false),
            'inventory_status' => $result['inventory_status'] ?? null,
            'warning' => $result['warning'] ?? null,
            'asset' => $result['asset'] ?? null,
            'unknown_scan' => $result['unknown_scan'] ?? null,
            'items' => $result['items'] ?? [],
            'spare' => $result['spare'] ?? null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Barcode or identifier to scan.')->required(),
            'scan_session_id' => $schema->integer()->description('Optional session id; defaults to active session.'),
            'quantity' => $schema->integer()->description('Quantity (default 1).'),
        ];
    }
}
