<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'kind',
        'name',
        'email',
        'password_hash',
        'department_id',
        'payload',
        'status',
        'decided_by',
        'decided_at',
        'reason',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'decided_at'  => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
