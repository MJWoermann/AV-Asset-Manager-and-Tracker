<?php

namespace App\Models;

use Database\Factories\RepairProcedureStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairProcedureStep extends Model
{
    /** @use HasFactory<RepairProcedureStepFactory> */
    use HasFactory;

    protected $fillable = [
        'repair_procedure_id',
        'sort_order',
        'title',
        'body',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(RepairProcedure::class, 'repair_procedure_id');
    }
}
