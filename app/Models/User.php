<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
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
        'is_approved' => 'boolean',
    ];

    protected $dates = [
        'birthday',
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
            // 'custom_fields' => 'array',
        ];
    }

    public function division()
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
                    ->contains(fn($role) => Str::startsWith(Str::upper($role), 'GSMS'));
        }

         // Allow GSMS if user is super_admin or has any role starting with 'GSMS'
        if ($panelId === 'HRIS') {
            return $this->hasRole('super_admin') ||
                collect($this->getRoleNames())
                    ->contains(fn($role) => Str::startsWith(Str::upper($role), 'HRIS'));
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
