<?php

namespace App\Models;

use Database\Factories\NotificationTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationTemplate extends Model
{
    /** @use HasFactory<NotificationTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'event_key',
        'name',
        'subject',
        'body',
        'is_active',
        'selected_fields',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'selected_fields' => 'array',
        ];
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(NotificationRecipient::class, 'notification_template_recipient')
            ->using(NotificationTemplateRecipient::class)
            ->withPivot(['required_fields'])
            ->withTimestamps();
    }
}
