<?php

namespace App\Http\Requests;

use App\Models\AssetInterface;
use App\Models\ConnectionBus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssetConnectionBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $assetId = (int) $this->route('asset')->id;

        return [
            'local_interface_id' => [
                'required',
                'integer',
                Rule::exists('asset_interfaces', 'id')->where('asset_id', $assetId),
            ],
            'connection_bus_id' => [
                'nullable',
                'integer',
                'exists:connection_buses,id',
                'required_without:new_bus_name',
            ],
            'new_bus_name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:connection_bus_id',
            ],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $local = AssetInterface::query()->with(['connectionType', 'buses'])->find($this->integer('local_interface_id'));

            if (! $local) {
                return;
            }

            if (! $local->connection_type_id || ! $local->connectionType) {
                $validator->errors()->add('local_interface_id', 'The local interface must have a connection type.');

                return;
            }

            if (! $local->connectionType->allows_bus) {
                $validator->errors()->add(
                    'local_interface_id',
                    'The selected interface type does not allow device buses.'
                );

                return;
            }

            if ($local->currentBus() !== null) {
                $validator->errors()->add(
                    'local_interface_id',
                    'This interface is already on a device bus. Leave it before joining another.'
                );

                return;
            }

            if ($this->filled('connection_bus_id')) {
                $bus = ConnectionBus::query()->find($this->integer('connection_bus_id'));

                if ($bus && (int) $bus->connection_type_id !== (int) $local->connection_type_id) {
                    $validator->errors()->add(
                        'connection_bus_id',
                        'The bus must use the same connection type as the interface.'
                    );
                }
            }
        });
    }
}
