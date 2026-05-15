<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentalPlanItem extends Model
{
    protected $fillable = [
        'departmental_plan_id',
        'faculty_profile_id',
        'category',
        'priority',
        'title',
        'description',
        'programs',
        'source',
        'status',
        'due_date',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'programs'     => 'array',
            'source'       => 'array',
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DepartmentalPlan::class, 'departmental_plan_id');
    }

    public function facultyProfile(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_profile_id');
    }
}
