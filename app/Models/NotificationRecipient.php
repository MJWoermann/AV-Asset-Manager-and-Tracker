<?php

namespace App\Models;

use Database\Factories\NotificationRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationRecipient extends Model
{
    /** @use HasFactory<NotificationRecipientFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'is_active',
        'role_tag',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(NotificationTemplate::class, 'notification_template_recipient')
            ->using(NotificationTemplateRecipient::class)
            ->withPivot(['required_fields'])
            ->withTimestamps();
    }
}
