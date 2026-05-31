<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    use HasRoles;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_number',
        'name',
        'email',
        'mobile_number',
        'address',
        'password',
        'avatar',
        'is_approved',
        'division_id',
        'avatar_url',
        // 'custom_fields',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'birthday' => 'date',
            // 'custom_fields' => 'array',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    // public function canAccessPanel(Panel $panel): bool
    // {
    //     if ($panel->getId() === 'admin') {
    //         return $this->hasRole('super_admin') && $this->is_approved;
    //     }

    //     if ($panel->getId() === 'GSMS') {
    //         return $this->hasAnyRole(['GSMS']) && $this->is_approved;
    //     }

    //     return $this->is_approved;
    // }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_approved) {
            return false;
        }

        $panelId = $panel->getId();

        // Always allow admin panel for approved super_admins
        if ($panelId === 'admin') {
            return $this->hasRole('super_admin');
        }

        // Allow GSMS if user is super_admin or has any role starting with 'GSMS'
        if ($panelId === 'GSMS') {
            return $this->hasRole('super_admin') ||
                collect($this->getRoleNames())
                    ->contains(fn ($role) => Str::startsWith(Str::upper($role), 'GSMS'));
        }

        // Allow GSMS if user is super_admin or has any role starting with 'GSMS'
        if ($panelId === 'HRIS') {
            return $this->hasRole('super_admin') ||
                collect($this->getRoleNames())
                    ->contains(fn ($role) => Str::startsWith(Str::upper($role), 'HRIS'));
        }

        // Allow GSMS if user is super_admin or has any role starting with 'GSMS'
        // if ($panelId === 'home') {
        //     return $this->hasRole('super_admin') ||
        //         collect($this->getRoleNames())
        //             ->contains(fn($role) => Str::startsWith(Str::upper($role), 'user'));
        // }

        // General case: allow access if approved
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');

        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }
}
