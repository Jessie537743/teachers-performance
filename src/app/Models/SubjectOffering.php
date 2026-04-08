<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectOffering extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subject_catalog_id',
        'department_id',
        'course',
        'year_level',
        'section',
        'semester',
        'school_year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subjectCatalog(): BelongsTo
    {
        return $this->belongsTo(SubjectCatalog::class, 'subject_catalog_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'subject_offering_id');
    }
}
