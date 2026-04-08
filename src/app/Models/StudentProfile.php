<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'student_id',
        'department_id',
        'course',
        'year_level',
        'section',
        'student_status',
        'semester',
        'school_year',
        'last_promoted_school_year',
        'last_promoted_semester',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(StudentSubjectAssignment::class, 'student_profile_id');
    }
}
