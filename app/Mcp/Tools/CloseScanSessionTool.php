<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\ScanSession;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Close an active scan session (defaults to the caller\'s active session).')]
class CloseScanSessionTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'scan-sessions');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'scan_session_id' => ['nullable', 'integer', 'exists:scan_sessions,id'],
        ]);

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
            return Response::error('No active scan session to close.');
        }

        $session->update(['status' => 'closed']);

        return $this->jsonResult([
            'message' => 'Scan session closed.',
            'session' => [
                'id' => $session->id,
                'status' => $session->fresh()->status,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scan_session_id' => $schema->integer()->description('Optional session id; defaults to active session.'),
        ];
    }
}
