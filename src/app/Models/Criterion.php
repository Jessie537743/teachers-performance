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
    ];

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
}
