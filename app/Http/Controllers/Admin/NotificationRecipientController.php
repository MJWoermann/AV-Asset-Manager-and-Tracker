<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNotificationRecipientRequest;
use App\Http\Requests\Admin\UpdateNotificationRecipientRequest;
use App\Models\NotificationRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationRecipientController extends Controller
{
    public function index(): View
    {
        return view('admin.email.recipients.index', [
            'recipients' => NotificationRecipient::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.email.recipients.form', [
            'recipient' => new NotificationRecipient(['is_active' => true]),
        ]);
    }

    public function store(StoreNotificationRecipientRequest $request): RedirectResponse
    {
        NotificationRecipient::query()->create($request->validatedRecipient());

        return redirect()
            ->route('admin.email.recipients.index')
            ->with('status', 'Recipient created.');
    }

    public function edit(NotificationRecipient $recipient): View
    {
        return view('admin.email.recipients.form', [
            'recipient' => $recipient,
        ]);
    }

    public function update(UpdateNotificationRecipientRequest $request, NotificationRecipient $recipient): RedirectResponse
    {
        $recipient->update($request->validatedRecipient());

        return redirect()
            ->route('admin.email.recipients.index')
            ->with('status', 'Recipient updated.');
    }

    public function destroy(NotificationRecipient $recipient): RedirectResponse
    {
        $recipient->delete();

        return redirect()
            ->route('admin.email.recipients.index')
            ->with('status', 'Recipient deleted.');
    }
}
