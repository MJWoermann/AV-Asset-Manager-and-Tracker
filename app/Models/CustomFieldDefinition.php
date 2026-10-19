<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    /** @var list<string> */
    public const LAYOUT_TYPES = ['spacer', 'divider'];

    protected $fillable = [
        'custom_field_set_id',
        'name',
        'slug',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function isLayoutElement(): bool
    {
        return in_array($this->type, self::LAYOUT_TYPES, true);
    }

    /**
     * Return a safe http(s) URL for opening in a new tab, or null when invalid.
     */
    public static function openableHttpUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            $value = 'https://'.$value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return $value;
    }

    public function fieldSet(): BelongsTo
    {
        return $this->belongsTo(CustomFieldSet::class, 'custom_field_set_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(AssetCustomFieldValue::class);
    }
}
