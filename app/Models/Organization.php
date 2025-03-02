<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Organization extends Model
{
    protected $table = 'm_organizations';

    // Optional: define the primary key if it's different from 'id'
    protected $primaryKey = 'id';

    // Disable timestamps if the table does not have `created_at` and `updated_at`
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';    //

    protected $appends = ['tree_name'];

     public static function getOrganizationOptions()
    {
        try {
            Log::info('Starting getOrganizationOptions');
            
            $organizations = self::departmentAbove()
                ->hierarchical()
                ->get();

            Log::info('Organizations retrieved:', [
                'count' => $organizations->count(),
                'first_record' => $organizations->first()
            ]);

            $mapped = $organizations->map(function ($org) {
                Log::info('Processing organization:', [
                    'id' => $org->id,
                    'name' => $org->name,
                    'indent_level' => $org->getIndentLevel(),
                    'tree_name' => $org->tree_name
                ]);

                return [
                    'id' => $org->id,
                    'tree_name' => $org->tree_name
                ];
            });

            Log::info('Mapped organizations:', ['mapped' => $mapped->toArray()]);

            $result = $organizations->mapWithKeys(function ($org) {
                if ($org->id && isset($org->tree_name)) {
                    return [$org->id => $org->tree_name];
                }
                Log::warning('Skipping organization due to missing data:', [
                    'id' => $org->id,
                    'has_tree_name' => isset($org->tree_name)
                ]);
                return [];
            })
            ->filter()
            ->toArray();

            Log::info('Final result:', ['result' => $result]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error in getOrganizationOptions:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getTreeNameAttribute()
    {
        $indent = str_repeat('-', $this->getIndentLevel());
        return $indent . $this->name;
    }


    protected function getIndentLevel(): int 
    {
        $idLength = strlen($this->id);
        
        $level = match(true) {
            $idLength == 3 => 0,     // Company level
            $idLength == 5 => 1,     // BOD level
            $idLength == 7 => 2,     // President Director
            $idLength == 9 => 3,     // Division
            $idLength == 11 => 4,    // Department
            $idLength == 12 => 5,    // Section
            default => 0
        };

        Log::info('Computing indent level:', [
            'id' => $this->id,
            'id_length' => $idLength,
            'level' => $level
        ]);

        return $level;
    }

    public function scopeDepartmentAbove($query)
    {
        $user = Auth::user();
        $companyId = null;
        
        if ($user && $user->emp_id) {
            $companyId = Employee::where('emp_id', $user->emp_id)->value('company_id');
        }

        Log::info('Department above query:', [
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'emp_id' => $user?->emp_id
        ]);

        return $query->when($companyId, function ($q) use ($companyId) {
                return $q->where('company_id', $companyId);
            })
            ->whereIn('level', ['Department', 'Division', 'President Director','Company','BOD BOC'])
            ->select([
                'id',
                'name',
                'level',
                'parent_id',
                'company_id',
                'sort'
            ]);
    }

    public function scopeHierarchical($query)
    {
        return $query->orderBy('id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id', 'id');
    }

    public function scopeForCurrentCompany($query)
    {
        $user = Auth::user();
        if ($user && $user->emp_id) {
            $userCompanyId = Employee::where('emp_id', $user->emp_id)->value('company_id');
            return $query->where('company_id', $userCompanyId);
        }
        return $query;
    }


}
