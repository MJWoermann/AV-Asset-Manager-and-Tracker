<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationEventKey;
use App\Services\NotificationFieldCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin:settings') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'selected_fields' => array_values(array_filter((array) $this->input('selected_fields', []))),
            'recipient_ids' => array_values(array_filter((array) $this->input('recipient_ids', []))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $templateId = $this->route('template')?->id;

        return [
            'event_key' => [
                'required',
                Rule::in(array_keys(NotificationEventKey::options())),
                Rule::unique('notification_templates', 'event_key')->ignore($templateId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['sometimes', 'boolean'],
            'selected_fields' => ['nullable', 'array'],
            'selected_fields.*' => ['string', 'max:120'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer', 'exists:notification_recipients,id'],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['nullable', 'array'],
            'required_fields.*.*' => ['string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedTemplate(): array
    {
        $data = $this->validated();
        $selected = array_values(array_unique($data['selected_fields'] ?? []));
        $catalog = app(NotificationFieldCatalog::class);
        $allowed = $catalog->allKeys();

        foreach ($selected as $key) {
            if (! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages([
                    'selected_fields' => 'Unknown field: '.$key,
                ]);
            }
        }

        $placeholders = $catalog->placeholdersIn($data['subject'], $data['body']);
        foreach ($placeholders as $key) {
            if ($selected !== [] && ! in_array($key, $selected, true) && ! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages([
                    'body' => 'Placeholder {{'.$key.'}} is not a known field.',
                ]);
            }
            if ($selected !== [] && ! in_array($key, $selected, true)) {
                throw ValidationException::withMessages([
                    'body' => 'Placeholder {{'.$key.'}} must be included in selected fields.',
                ]);
            }
        }

        return [
            'event_key' => $data['event_key'],
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'is_active' => $this->boolean('is_active'),
            'selected_fields' => $selected,
        ];
    }

    /**
     * @return array<int, array{required_fields: list<string>}>
     */
    public function recipientSyncPayload(): array
    {
        $recipientIds = array_map('intval', $this->validated()['recipient_ids'] ?? []);
        $requiredMap = $this->input('required_fields', []);
        $selected = array_values(array_unique($this->validated()['selected_fields'] ?? []));
        $sync = [];

        foreach ($recipientIds as $id) {
            $required = array_values(array_unique(array_filter((array) ($requiredMap[$id] ?? []))));
            foreach ($required as $key) {
                if ($selected !== [] && ! in_array($key, $selected, true)) {
                    throw ValidationException::withMessages([
                        'required_fields' => 'Required field '.$key.' must be selected on the template.',
                    ]);
                }
            }
            $sync[$id] = ['required_fields' => $required];
        }

        return $sync;
    }
}
