<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criteria';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'criteria_id');
    }

    public function evaluatorGroups(): HasMany
    {
        return $this->hasMany(CriterionEvaluatorGroup::class, 'criterion_id');
    }

    public function personnelTypes(): HasMany
    {
        return $this->hasMany(CriterionPersonnelType::class, 'criterion_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForEvaluatorGroup(Builder $query, string $group): Builder
    {
        return $query->whereHas(
            'evaluatorGroups',
            fn (Builder $q) => $q->where('evaluator_group', $group)
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForPersonnelType(Builder $query, string $personnelType): Builder
    {
        return $query->whereHas(
            'personnelTypes',
            fn (Builder $q) => $q->where('personnel_type', $personnelType)
        );
    }

    /**
     * Return this criterion's weight for a specific evaluator group. Falls back
     * to the legacy criterion-level weight when the per-group row has no value
     * (older data migrated before per-group weights existed).
     */
    public function weightForGroup(string $group): float
    {
        $row = $this->evaluatorGroups
            ->firstWhere('evaluator_group', $group);

        if ($row === null) {
            return (float) ($this->weight ?? 0);
        }

        return (float) ($row->weight ?? $this->weight ?? 0);
    }
}
