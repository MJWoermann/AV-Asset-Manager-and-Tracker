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
