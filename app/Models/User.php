<?php

namespace App\Models;

use App\Models\OneTimePassword;
use App\Services\Google2faService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method bool hasRole(string $key)
 * @method bool hasAnyRole(array $keys)
 * @method array roleKeys()
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'is_first_login',
        'signature_path',
        'google2fa_secret',
        'google2fa_enabled',
        'google2fa_recovery_codes',
        'google2fa_enabled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute()
    {
        $middle = $this->middle_name ? substr($this->middle_name, 0, 1) . '.' : '';
        return trim("{$this->first_name} {$middle} {$this->last_name}");
    }
    public function getFormalNameAttribute()
    {
        $middle = $this->middle_name ? substr($this->middle_name, 0, 1) . '.' : '';
        return trim("{$this->last_name}, {$this->first_name} {$middle}");
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function hasRole(string $key): bool
    {
        return $this->roles->contains('key', $key);
    }

    public function hasAnyRole(array $keys): bool
    {
        return $this->roles->whereIn('key', $keys)->isNotEmpty();
    }

    public function roleKeys(): array
    {
        return $this->roles->pluck('key')->all();
    }

    public function oneTimePasswords(): HasMany
    {
        return $this->hasMany(OneTimePassword::class);
    }

    public function otps(): HasMany
    {
        return $this->hasMany(OneTimePassword::class);
    }

    public function generateOneTimePassword(int $expiresInMinutes = 5): OneTimePassword
    {
        // Clean up expired OTPs for this user
        \Illuminate\Support\Facades\DB::statement("DELETE FROM `lats_otp_table` WHERE `user_id` = ? AND `expires_at` < NOW()", [$this->id]);

        // Generate a 6-digit OTP code first
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Use MySQL NOW() function for expiration to avoid timezone issues
        $expiresAt = now()->addMinutes($expiresInMinutes);

        // Insert OTP using direct query without prefix
        \Illuminate\Support\Facades\DB::statement("INSERT INTO `lats_otp_table` (`user_id`, `code`, `expires_at`, `ip_address`, `user_agent`, `used`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())", [
            $this->id,
            $code,
            $expiresAt,
            request()->ip(),
            request()->userAgent(),
            false,
        ]);

        // Retrieve the created OTP without prefix - use the code to find it
        $otp = \Illuminate\Support\Facades\DB::select("SELECT * FROM `lats_otp_table` WHERE `user_id` = ? AND `code` = ? ORDER BY `id` DESC LIMIT 1", [$this->id, $code])[0] ?? null;

        // Log OTP creation for debugging
        \Illuminate\Support\Facades\Log::info('OTP Created in database', [
            'id' => $otp->id,
            'code' => $otp->code,
            'user_id' => $otp->user_id,
            'expires_at' => $otp->expires_at,
            'used' => $otp->used,
            'created_at' => $otp->created_at
        ]);

        // Create OneTimePassword model instance for compatibility
        $otpModel = new OneTimePassword();
        $otpModel->id = $otp->id;
        $otpModel->user_id = $otp->user_id;
        $otpModel->code = $otp->code;
        $otpModel->expires_at = $otp->expires_at;
        $otpModel->ip_address = $otp->ip_address;
        $otpModel->user_agent = $otp->user_agent;
        $otpModel->used = $otp->used;

        return $otpModel;
    }

    public function consumeOneTimePassword(string $code): bool
    {
        // Log what we're looking for
        $currentTime = now();
        \Illuminate\Support\Facades\Log::info('Attempting to verify OTP', [
            'code' => $code,
            'user_id' => $this->id,
            'now' => $currentTime->toIso8601String(),
            'now_mysql_format' => $currentTime->toDateTimeString()
        ]);

        // First, let's see what OTPs match the code regardless of status
        $matchingOtps = \Illuminate\Support\Facades\DB::select("SELECT * FROM `lats_otp_table` WHERE `user_id` = ? AND `code` = ?", [$this->id, $code]);
        \Illuminate\Support\Facades\Log::info('Matching OTPs', [
            'code' => $code,
            'user_id' => $this->id,
            'matching_otps' => $matchingOtps
        ]);

        // Try to find the specific OTP with conditions
        $otp = \Illuminate\Support\Facades\DB::select("SELECT * FROM `lats_otp_table` WHERE `user_id` = ? AND `code` = ? AND `used` = 0 AND `expires_at` > ?", [$this->id, $code, $currentTime])[0] ?? null;

        if (!$otp) {
            // Log why OTP was not found for debugging
            \Illuminate\Support\Facades\Log::info('OTP not found in raw SQL', [
                'code' => $code,
                'user_id' => $this->id,
                'available_otps' => \Illuminate\Support\Facades\DB::select("SELECT code, expires_at, used FROM `lats_otp_table` WHERE `user_id` = ? AND `used` = 0 AND `expires_at` > ?", [$this->id, $currentTime])
            ]);
            return false;
        }

        // Update as used using raw SQL
        \Illuminate\Support\Facades\DB::statement("UPDATE `lats_otp_table` SET `used` = 1 WHERE `id` = ?", [$otp->id]);

        return true;
    }

    public function hasValidOneTimePassword(): bool
    {
        return $this->oneTimePasswords()
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();
    }

    // Google Authenticator Methods
    public function enableGoogle2fa(): string
    {
        $google2fa = new Google2faService();
        $secret = $google2fa->generateSecret();
        $encryptedSecret = $google2fa->encryptSecret($secret);

        $this->google2fa_secret = $encryptedSecret;
        $this->google2fa_enabled = false; // Not enabled until verified
        $this->google2fa_recovery_codes = $this->generateRecoveryCodes();
        $this->save();

        return $secret; // Return plain secret for QR code generation
    }

    public function confirmGoogle2fa(string $code): bool
    {
        $google2fa = new Google2faService();
        $secret = $google2fa->decryptSecret($this->google2fa_secret);

        if ($google2fa->verifyCode($secret, $code)) {
            $this->google2fa_enabled = true;
            $this->google2fa_enabled_at = now();
            $this->save();
            return true;
        }

        return false;
    }

    public function disableGoogle2fa(): void
    {
        $this->google2fa_secret = null;
        $this->google2fa_enabled = false;
        $this->google2fa_recovery_codes = null;
        $this->google2fa_enabled_at = null;
        $this->save();
    }

    public function verifyGoogle2faCode(string $code): bool
    {
        if (!$this->google2fa_enabled || !$this->google2fa_secret) {
            return false;
        }

        $google2fa = new Google2faService();
        $secret = $google2fa->decryptSecret($this->google2fa_secret);

        // Debug: log what codes we're generating
        $currentCode = $google2fa->generateCode($secret);
        \Log::info('Google2fa code comparison', [
            'user_id' => $this->id,
            'user_code' => $code,
            'generated_code' => $currentCode,
            'match' => hash_equals($currentCode, $code),
        ]);

        return $google2fa->verifyCode($secret, $code);
    }

    public function hasGoogle2faEnabled(): bool
    {
        return $this->google2fa_enabled && !empty($this->google2fa_secret);
    }

    public function getGoogle2faSecret(): ?string
    {
        if (!$this->google2fa_secret) {
            return null;
        }

        $google2fa = new Google2faService();
        return $google2fa->decryptSecret($this->google2fa_secret);
    }

    public function getGoogle2faQrCodeUrl(): ?string
    {
        if (!$this->google2fa_secret) {
            return null;
        }

        $google2fa = new Google2faService();
        $secret = $google2fa->decryptSecret($this->google2fa_secret);

        return $google2fa->getQrCodeUrl($secret, $this->email, 'LATS');
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT));
        }
        return $codes;
    }

    public function verifyRecoveryCode(string $code): bool
    {
        if (!$this->google2fa_recovery_codes) {
            return false;
        }

        // Handle both string (from database) and array (from manual handling)
        $recoveryCodes = is_string($this->google2fa_recovery_codes)
            ? json_decode($this->google2fa_recovery_codes, true)
            : $this->google2fa_recovery_codes;

        $code = strtoupper(trim($code));

        if (($key = array_search($code, $recoveryCodes)) !== false) {
            // Remove used recovery code
            unset($recoveryCodes[$key]);
            $this->google2fa_recovery_codes = json_encode(array_values($recoveryCodes));
            $this->save();
            return true;
        }

        return false;
    }

    public function getRemainingRecoveryCodes(): array
    {
        if (!$this->google2fa_recovery_codes) {
            return [];
        }

        // Always decode since the field stores JSON strings
        $decoded = json_decode($this->google2fa_recovery_codes, true);
        return is_array($decoded) ? $decoded : [];
    }
}
