<?php

namespace App\Models;

use App\Traits\DeleteTrait;
use App\Traits\LogTraits;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia, HasName
{
    use DeleteTrait,
        HasFactory,
        HasRoles,
        InteractsWithMedia,
        LogsActivity,
        LogTraits,
        Notifiable,
        SoftDeletes;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (auth()->check() && auth()->id() === $user->id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot delete your own account.',
                ]);
            }

            if ($user->hasRole('super_admin') && auth()->check() && ! auth()->user()->hasRole('super_admin')) {
                throw ValidationException::withMessages([
                    'user' => 'Only a super admin can delete another super admin.',
                ]);
            }

            if ($user->hasRole('super_admin') && ! $user->trashed() && static::role('super_admin')->count() <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'The last remaining super admin cannot be deleted.',
                ]);
            }
        });
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'phone',
        'email',
        'password',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'first_name', 'last_name', 'username', 'phone', 'email'])
            ->logOnlyDirty()
            ->useLogName('User');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function prints(): HasMany
    {
        return $this->hasMany(ModalPrint::class, 'printed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
