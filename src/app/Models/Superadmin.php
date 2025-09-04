<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Superadmin extends Authenticatable
{
    use Notifiable, HasUuids;

    protected $table = 'superadmins';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Mutator: otomatis hash password kalau di-set plain text
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value) && strlen($value) < 60) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /**
     * Ambil inisial dari nama (contoh: "Si Amba" => "SA")
     */
    public function initials(): string
    {
        if (empty($this->name)) {
            return '';
        }

        $words = preg_split('/\s+/', trim($this->name));
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }
}
