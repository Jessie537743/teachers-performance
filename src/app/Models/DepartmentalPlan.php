<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentalPlan extends Model
{
    protected $fillable = [
        'department_id',
        'dean_user_id',
        'school_year',
        'semester',
        'summary',
        'roll_up',
        'generated_from',
        'model_version',
        'status',
        'generated_by',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'roll_up'        => 'array',
            'generated_from' => 'array',
            'completed_at'   => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DepartmentalPlanItem::class);
    }
}
