<?php

namespace App\Policies;

use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave_application');
    }

    public function view(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('view_leave_application');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_application');
    }

    public function update(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('update_leave_application');
    }

    public function delete(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('delete_leave_application');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_leave_application');
    }

    public function forceDelete(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('force_delete_leave_application');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_leave_application');
    }

    public function restore(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('restore_leave_application');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_leave_application');
    }

    public function replicate(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('replicate_leave_application');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_leave_application');
    }
}
