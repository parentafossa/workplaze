<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super admin can view all
        if ($user->can('view_any_employee')) {
            return true;
        }

        // Users can view if they have access to any company
        return $user->companies()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        // Super admin can view any employee
        if ($user->can('view_employee')) {
            return true;
        }

        // Users can only view employees from companies they have access to
        return $user->companies()
            ->where('m_companies.id', $employee->company_id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // First check permission
        if (!$user->can('create_employee')) {
            return false;
        }

        // Then verify they have access to at least one company
        return $user->companies()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        // Super admin can update any employee
        if ($user->can('update_employee')) {
            return true;
        }

        // Users can only update employees from companies they have access to
        return $user->companies()
            ->where('m_companies.id', $employee->company_id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        // Super admin can delete any employee
        if ($user->can('delete_employee')) {
            return true;
        }

        // Users can only delete employees from companies they have access to
        return $user->companies()
            ->where('m_companies.id', $employee->company_id)
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Employee $employee): bool
    {
        // First check permission
        if (!$user->can('restore_employee')) {
            return false;
        }

        // Users can only restore employees from companies they have access to
        return $user->companies()
            ->where('m_companies.id', $employee->company_id)
            ->exists();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        // First check permission
        if (!$user->can('force_delete_employee')) {
            return false;
        }

        // Users can only force delete employees from companies they have access to
        return $user->companies()
            ->where('m_companies.id', $employee->company_id)
            ->exists();
    }
}