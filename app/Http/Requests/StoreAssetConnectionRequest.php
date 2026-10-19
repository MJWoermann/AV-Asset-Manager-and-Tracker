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
        $assetId = (int) $this->route('asset')->id;
        $protocolNames = ConnectionType::query()->orderBy('sort_order')->orderBy('name')->pluck('name')->all();

        return [
            'local_interface_id' => [
                'required',
                'integer',
                Rule::exists('asset_interfaces', 'id')->where('asset_id', $assetId),
            ],
            'peer_asset_id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id'),
                Rule::notIn([(string) $assetId, $assetId]),
            ],
            'peer_interface_id' => [
                'nullable',
                'integer',
                'exists:asset_interfaces,id',
                'different:local_interface_id',
                'required_without:new_peer_interface_name',
            ],
            'new_peer_interface_name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:peer_interface_id',
            ],
            'protocol' => ['nullable', 'string', 'max:255', Rule::in($protocolNames)],
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

            $local = AssetInterface::query()->with('connectionType')->find($this->integer('local_interface_id'));
            $peerAssetId = $this->integer('peer_asset_id');

            if (! $local) {
                return;
            }

            if (! $local->connection_type_id || ! $local->connectionType) {
                $validator->errors()->add('local_interface_id', 'The local interface must have a connection type.');

                return;
            }

            if ((int) $local->asset_id === $peerAssetId) {
                $validator->errors()->add('peer_asset_id', 'Connections must link interfaces on different assets.');

                return;
            }

            $type = $local->connectionType;

            if ($type->allows_protocol && blank($this->input('protocol'))) {
                $validator->errors()->add('protocol', 'A protocol is required for this connection type.');
            }

            if ($this->filled('new_peer_interface_name')) {
                return;
            }

            $peer = AssetInterface::query()->find($this->integer('peer_interface_id'));

            if (! $peer) {
                return;
            }

            if ((int) $peer->asset_id !== $peerAssetId) {
                $validator->errors()->add('peer_interface_id', 'The peer interface must belong to the selected peer asset.');
            }

            if ((int) $local->asset_id === (int) $peer->asset_id) {
                $validator->errors()->add('peer_interface_id', 'Connections must link interfaces on different assets.');
            }

            if ($peer->connection_type_id !== null
                && (int) $peer->connection_type_id !== (int) $local->connection_type_id) {
                $validator->errors()->add(
                    'peer_interface_id',
                    'The peer interface must use the same connection type as the local interface.'
                );
            }
        });
    }
}
