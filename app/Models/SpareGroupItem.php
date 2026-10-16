<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpareGroupItem extends Model
{
    protected $fillable = [
        'spare_group_id',
        'spare_part_id',
        'quantity',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SpareGroup::class, 'spare_group_id');
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function isShort(): bool
    {
        $onHand = (int) ($this->sparePart?->quantity_on_hand ?? 0);

        return $onHand < (int) $this->quantity;
    }
}
