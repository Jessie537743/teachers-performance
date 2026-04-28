<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivationCode extends Model
{
    protected $connection = 'central';
    protected $table = 'activation_codes';

    protected $fillable = [
        'tenant_id',
        'code',
        'plan',
        'intended_admin_name',
        'intended_admin_email',
        'status',
        'expires_at',
        'redeemed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'redeemed_at' => 'datetime',
            'revoked_at'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a unique 12-char code formatted XXXX-YYYY-ZZZZ from an
     * unambiguous 32-char alphabet (no 0/O, no 1/I).
     */
    public static function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $alphaLen = strlen($alphabet);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $chars = '';
            for ($i = 0; $i < 12; $i++) {
                $chars .= $alphabet[random_int(0, $alphaLen - 1)];
            }
            $candidate = substr($chars, 0, 4) . '-' . substr($chars, 4, 4) . '-' . substr($chars, 8, 4);

            if (! self::where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Could not generate a unique activation code after 5 attempts.');
    }

    public function isRedeemable(): bool
    {
        return $this->status === 'unredeemed' && $this->expires_at->isFuture();
    }
}
