<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeanEvaluationFeedback extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'dean_user_id',
        'faculty_id',
        'semester',
        'school_year',
        'comment',
        'recommendation',
        'total_average',
        'performance_level',
        'weighted_percentage',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_average'       => 'decimal:2',
            'weighted_percentage' => 'decimal:2',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }
}
