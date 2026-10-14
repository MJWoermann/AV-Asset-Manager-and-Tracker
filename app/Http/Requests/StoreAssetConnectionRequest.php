<?php

namespace App\Http\Requests;

use App\Models\AssetInterface;
use App\Models\ConnectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'local_interface_id' => [
                'required',
                'integer',
                Rule::exists('asset_interfaces', 'id')->where('asset_id', $this->route('asset')->id),
            ],
            'peer_interface_id' => [
                'required',
                'integer',
                'exists:asset_interfaces,id',
                'different:local_interface_id',
            ],
            'connection_type_id' => ['required', 'integer', 'exists:connection_types,id'],
            'protocol' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $local = AssetInterface::query()->find($this->integer('local_interface_id'));
            $peer = AssetInterface::query()->find($this->integer('peer_interface_id'));
            $type = ConnectionType::query()->find($this->integer('connection_type_id'));

            if (! $local || ! $peer || ! $type) {
                return;
            }

            if ((int) $local->asset_id === (int) $peer->asset_id) {
                $validator->errors()->add('peer_interface_id', 'Connections must link interfaces on different assets.');
            }

            if ($type->allows_protocol && blank($this->input('protocol'))) {
                $validator->errors()->add('protocol', 'A protocol is required for this connection type.');
            }
        });
    }
}
