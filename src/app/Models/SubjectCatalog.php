<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectCatalog extends Model
{
    protected $table = 'subject_catalog';

    public $timestamps = false;

    protected $fillable = [
        'subject_code',
        'subject_title',
        'units',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'decimal:1',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class, 'subject_catalog_id');
    }
}
