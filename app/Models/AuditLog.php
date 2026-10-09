<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Immutable audit record. Do not update or delete.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return list<string>
     */
    public function changedAttributeKeys(): array
    {
        $keys = array_unique(array_merge(
            array_keys($this->old_values ?? []),
            array_keys($this->new_values ?? [])
        ));

        return array_values(array_filter(
            $keys,
            fn (string $key) => ! in_array($key, ['id', 'created_at', 'updated_at', 'password', 'remember_token'], true)
        ));
    }

    public function formatValue(mixed $value, int $limit = 60): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return Str::limit((string) $value, $limit);
        }

        return Str::limit((string) json_encode($value), $limit);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
    }
}
