<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class NotificationTemplateRecipient extends Pivot
{
    protected $table = 'notification_template_recipient';

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
        ];
    }
}
