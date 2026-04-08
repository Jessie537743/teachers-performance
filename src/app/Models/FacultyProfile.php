<?php

namespace App\Models;

use App\Enums\FacultyDepartmentPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'department_id',
        'department_position',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'department_position' => FacultyDepartmentPosition::class,
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

    /**
     * Personnel slice used to load evaluation criteria (student / peer / self / dean evaluator).
     * Dean/Head (Role/Position) uses dean_head_teaching or dean_head_non_teaching — the
     * Academic Administrators rubric (A/B/C blocks) from AcademicAdministratorsCriteriaSeeder / admin.
     */
    public function evaluationCriteriaPersonnelType(): string
    {
        $base = $this->departmentCriteriaSlice();

        if ($this->department_position === FacultyDepartmentPosition::DeanHead) {
            return $base === 'non-teaching'
                ? 'dean_head_non_teaching'
                : 'dean_head_teaching';
        }

        return $base;
    }

    /**
     * Teaching vs non-teaching slice from department type only (fallback when position is not Dean/Head).
     */
    public function departmentCriteriaSlice(): string
    {
        if (! $this->department_id) {
            return 'teaching';
        }

        $dept = $this->relationLoaded('department')
            ? $this->department
            : $this->department()->first();

        return ($dept && $dept->department_type === 'non-teaching')
            ? 'non-teaching'
            : 'teaching';
    }

    public function evaluationCriteriaPersonnelTypeLabel(): string
    {
        $t = $this->evaluationCriteriaPersonnelType();

        return match ($t) {
            'dean_head_teaching' => 'Dean/Head (teaching dept.)',
            'dean_head_non_teaching' => 'Dean/Head (non-teaching dept.)',
            'non-teaching' => 'Non-teaching personnel',
            default => 'Teaching personnel',
        };
    }

    public function isDeanOrDepartmentHead(): bool
    {
        return $this->department_position === FacultyDepartmentPosition::DeanHead;
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class, 'faculty_id');
    }

    public function evaluationAnswers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'faculty_id');
    }

    public function evaluationFeedback(): HasMany
    {
        return $this->hasMany(EvaluationFeedback::class, 'faculty_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'faculty_id');
    }

    public function deanEvaluationAnswers(): HasMany
    {
        return $this->hasMany(DeanEvaluationAnswer::class, 'faculty_id');
    }

    public function deanEvaluationFeedback(): HasMany
    {
        return $this->hasMany(DeanEvaluationFeedback::class, 'faculty_id');
    }

    public function peerEvaluationAnswersAsEvaluator(): HasMany
    {
        return $this->hasMany(FacultyPeerEvaluationAnswer::class, 'evaluator_faculty_id');
    }

    public function peerEvaluationAnswersAsEvaluatee(): HasMany
    {
        return $this->hasMany(FacultyPeerEvaluationAnswer::class, 'evaluatee_faculty_id');
    }

    public function peerEvaluationFeedbackAsEvaluator(): HasMany
    {
        return $this->hasMany(FacultyPeerEvaluationFeedback::class, 'evaluator_faculty_id');
    }

    public function peerEvaluationFeedbackAsEvaluatee(): HasMany
    {
        return $this->hasMany(FacultyPeerEvaluationFeedback::class, 'evaluatee_faculty_id');
    }
}
