<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetStockToken;
use App\Models\CustomFieldDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KanbanTagService
{
    public const DEFAULT_LABELS = ['kanban-a', 'kanban-b'];

    /**
     * Issue a token for the given label. An active tag must be revoked first so a printed QR stays valid.
     *
     * @return array{token: AssetStockToken, plain_token: string, guest_url: string}
     */
    public function rotate(Asset $asset, string $label): array
    {
        $label = trim($label) !== '' ? trim($label) : 'kanban-a';

        $existing = AssetStockToken::query()
            ->where('asset_id', $asset->id)
            ->where('label', $label)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'label' => 'Revoke the active '.$label.' tag before issuing a new one.',
            ]);
        }

        $plain = Str::random(48);
        $hash = hash('sha256', $plain);

        $token = AssetStockToken::query()->create([
            'asset_id' => $asset->id,
            'token_hash' => $hash,
            'label' => $label,
        ]);

        return [
            'token' => $token,
            'plain_token' => $plain,
            'guest_url' => $this->guestUrl($plain),
        ];
    }

    public function revoke(AssetStockToken $token): void
    {
        if ($token->revoked_at === null) {
            $token->update(['revoked_at' => now()]);
        }
    }

    public function findActiveByPlainToken(string $plainToken): ?AssetStockToken
    {
        $hash = hash('sha256', $plainToken);

        return AssetStockToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
    }

    public function guestUrl(string $plainToken): string
    {
        return url('/stock/t/'.$plainToken);
    }

    /**
     * @return Collection<int, array{token: AssetStockToken, guest_url: string|null, plain_token: string|null, sku: string|null, supplier: string|null, reorder_url: string|null}>
     */
    public function activeTagsForPrint(Asset $asset, array $plainTokensById = []): Collection
    {
        $sku = $this->restockingField($asset, 'sku');
        $supplier = $this->restockingField($asset, 'supplier') ?: $asset->supplier;
        $reorderUrl = $this->restockingField($asset, 'url');

        return $asset->stockTokens()
            ->whereNull('revoked_at')
            ->orderBy('label')
            ->get()
            ->map(function (AssetStockToken $token) use ($plainTokensById, $sku, $supplier, $reorderUrl): array {
                $plain = $plainTokensById[$token->id] ?? null;

                return [
                    'token' => $token,
                    'guest_url' => $plain ? $this->guestUrl($plain) : null,
                    'plain_token' => $plain,
                    'sku' => $sku,
                    'supplier' => $supplier,
                    'reorder_url' => $reorderUrl,
                ];
            });
    }

    /**
     * Ensure default kanban labels exist; returns plain tokens for newly created ones.
     *
     * @return array<int, string> token id => plain token
     */
    public function ensureDefaultTags(Asset $asset): array
    {
        $plainById = [];

        foreach (self::DEFAULT_LABELS as $label) {
            $exists = AssetStockToken::query()
                ->where('asset_id', $asset->id)
                ->where('label', $label)
                ->whereNull('revoked_at')
                ->exists();

            if (! $exists) {
                $created = $this->rotate($asset, $label);
                $plainById[$created['token']->id] = $created['plain_token'];
            }
        }

        return $plainById;
    }

    public function restockingField(Asset $asset, string $slug): ?string
    {
        $asset->loadMissing(['customFieldValues.definition.fieldSet']);

        $definitionIds = CustomFieldDefinition::query()
            ->where('slug', $slug)
            ->whereHas('fieldSet', fn ($q) => $q->where('slug', 'restocking'))
            ->pluck('id');

        if ($definitionIds->isEmpty()) {
            return null;
        }

        $value = $asset->customFieldValues
            ->first(fn ($row) => $definitionIds->contains($row->custom_field_definition_id));

        $raw = $value?->value;
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        return trim((string) $raw);
    }
}
