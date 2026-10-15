<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationEventKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNotificationTemplateRequest;
use App\Http\Requests\Admin\UpdateNotificationTemplateRequest;
use App\Models\Asset;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Services\NotificationFieldCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email.templates.index', [
            'templates' => NotificationTemplate::query()->withCount('recipients')->orderBy('event_key')->get(),
            'eventLabels' => NotificationEventKey::options(),
        ]);
    }

    public function create(NotificationFieldCatalog $catalog): View
    {
        // #region agent log
        $debugLog = base_path('debug-f3be50.log');
        $writeDebug = static function (string $hypothesisId, string $message, array $data) use ($debugLog): void {
            file_put_contents($debugLog, json_encode([
                'sessionId' => 'f3be50',
                'hypothesisId' => $hypothesisId,
                'location' => 'NotificationTemplateController.php:create',
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        };
        $writeDebug('C', 'create() entered', ['path' => 'admin/email/templates/create']);
        $snippets = [
            'A' => "{{ '{{asset.name}}' }}",
            'A_escaped' => '@{{asset.name}}',
            'B' => "{{ '{{custom.mac_address_2}}' }}",
            'B_escaped' => '@{{custom.mac_address_2}}',
        ];
        foreach ($snippets as $id => $src) {
            $compiled = Blade::compileString($src);
            $writeDebug($id[0], 'snippet compiled', ['id' => $id, 'src' => $src, 'compiled' => $compiled]);
            try {
                ob_start();
                eval('?>'.$compiled);
                $rendered = ob_get_clean();
                $writeDebug($id[0], 'snippet eval ok', ['id' => $id, 'rendered' => $rendered]);
            } catch (\Throwable $e) {
                ob_end_clean();
                $writeDebug($id[0], 'snippet eval fail', [
                    'id' => $id,
                    'error' => $e->getMessage(),
                    'class' => $e::class,
                ]);
            }
        }
        $formCompiled = Blade::compileString((string) file_get_contents(resource_path('views/admin/email/templates/form.blade.php')));
        $writeDebug('E', 'full form compiled length', ['bytes' => strlen($formCompiled)]);
        $helpLine = null;
        foreach (explode("\n", $formCompiled) as $i => $line) {
            if (str_contains($line, 'asset.name') || str_contains($line, 'mac_address_2') || str_contains($line, 'placeholders')) {
                $helpLine = ['line' => $i + 1, 'php' => $line];
                break;
            }
        }
        $writeDebug('A', 'compiled help-line excerpt', ['excerpt' => $helpLine]);
        // #endregion

        return view('admin.email.templates.form', $this->formData($catalog, new NotificationTemplate([
            'is_active' => true,
            'selected_fields' => ['asset.name', 'asset.mac_address'],
        ])));
    }

    public function store(StoreNotificationTemplateRequest $request): RedirectResponse
    {
        $data = $request->validatedTemplate();
        $template = NotificationTemplate::query()->create($data);
        $template->recipients()->sync($request->recipientSyncPayload());

        return redirect()
            ->route('admin.email.templates.index')
            ->with('status', 'Template created.');
    }

    public function edit(NotificationTemplate $template, NotificationFieldCatalog $catalog): View
    {
        $template->load('recipients');

        return view('admin.email.templates.form', $this->formData($catalog, $template));
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $template): RedirectResponse
    {
        $template->update($request->validatedTemplate());
        $template->recipients()->sync($request->recipientSyncPayload());

        return redirect()
            ->route('admin.email.templates.index')
            ->with('status', 'Template updated.');
    }

    public function destroy(NotificationTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('admin.email.templates.index')
            ->with('status', 'Template deleted.');
    }

    public function preview(Request $request, NotificationTemplate $template, NotificationFieldCatalog $catalog): View
    {
        $assetId = $request->integer('asset_id') ?: null;
        $asset = $assetId
            ? Asset::query()->with(['itemType', 'location', 'customFieldValues.definition'])->find($assetId)
            : Asset::query()->with(['itemType', 'location', 'customFieldValues.definition'])->first();

        $values = $asset
            ? $catalog->resolve($asset, $request->user())
            : collect($catalog->allKeys())->mapWithKeys(fn (string $key): array => [$key => 'sample'])->all();

        return view('admin.email.templates.preview', [
            'template' => $template,
            'asset' => $asset,
            'subject' => $catalog->render($template->subject, $values),
            'body' => $catalog->render($template->body, $values),
            'assets' => Asset::query()->orderBy('name')->limit(100)->get(['id', 'name']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(NotificationFieldCatalog $catalog, NotificationTemplate $template): array
    {
        return [
            'template' => $template,
            'catalog' => $catalog->catalog(),
            'eventKeys' => NotificationEventKey::options(),
            'recipients' => NotificationRecipient::query()->orderBy('name')->get(),
            'selectedRecipientIds' => $template->exists
                ? $template->recipients->pluck('id')->all()
                : [],
            'requiredByRecipient' => $template->exists
                ? $template->recipients->mapWithKeys(fn ($recipient): array => [
                    (string) $recipient->id => is_array($recipient->pivot->required_fields)
                        ? $recipient->pivot->required_fields
                        : [],
                ])->all()
                : [],
        ];
    }
}
