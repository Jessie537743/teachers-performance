<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Signature extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'signature_path',
        'is_signatory',
    ];

    protected $casts = [
        'is_signatory' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the active signatory used on reports. Cached briefly for hot
     * report rendering paths.
     */
    public static function activeSignatory(): ?self
    {
        return Cache::remember('signature:active', 300, function () {
            return static::with('user')->where('is_signatory', true)->first();
        });
    }

    /**
     * Mark this signature as the sole active signatory.
     */
    public function markAsSignatory(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)
                ->where('is_signatory', true)
                ->update(['is_signatory' => false]);

            $this->forceFill(['is_signatory' => true])->save();
        });

        Cache::forget('signature:active');
    }

    public static function clearCache(): void
    {
        Cache::forget('signature:active');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
