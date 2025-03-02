<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Employee;
use App\Models\Company;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
	use HasPanelShield;
    use HasApiTokens;
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }
    
    public function employeeInfo()
    {
        return $this->hasOne(Employee::class, 'emp_id', 'emp_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'emp_id', 'emp_id');
    }
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'user_companies', 'user_id', 'company_id');
    }

    // Helper method to check if user has access to a company
    public function hasCompanyAccess($companyId): bool
    {
        return $this->is_admin || $this->companies->contains('id', $companyId);
    }

    // Get array of company IDs user has access to
    public function getCompanyIds(): array
    {
        return $this->companies->pluck('id')->toArray();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        /* \Log::info('Panel Access Check', [
            'user_id' => $this->id,
            'panel_id' => $panel->getId(),
            'has_admin_role' => $this->hasRole('admin'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ]);
        */
        
        return (
            (
                str_starts_with($this->emp_id, '1') || 
                str_starts_with($this->emp_id, '2') || 
                str_starts_with($this->emp_id, '3')) &&
            (
                str_ends_with($this->email, '@logisteed.id') || 
                str_ends_with($this->email, '@logisteed.com') || 
                str_ends_with($this->email, '@vantec-gl.com')
            )
        );
    }

    public function isAdmin()
    {
        return $this->is_admin === true;
    }
}
