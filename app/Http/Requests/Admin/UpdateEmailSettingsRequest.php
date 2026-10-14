<?php

namespace App\Http\Requests\Admin;

use App\Support\EmailIntegrationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'driver' => ['required', Rule::in(EmailIntegrationSettings::DRIVERS)],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'outlook_mailbox' => ['nullable', 'string', 'max:255'],
            'inbound_folder' => ['nullable', 'string', 'max:255'],
            'poll_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['nullable', Rule::in(['ssl', 'tls', 'none'])],
            'imap_username' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedSettings(): array
    {
        $data = $this->validated();
        $data['enabled'] = $this->boolean('enabled');

        if (($data['imap_encryption'] ?? null) === 'none') {
            $data['imap_encryption'] = null;
        }

        return $data;
    }
}
