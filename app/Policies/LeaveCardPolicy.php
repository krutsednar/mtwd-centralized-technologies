<?php

namespace App\Policies;

use App\Models\LeaveCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave_card');
    }

    public function view(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('view_leave_card');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_card');
    }

    public function update(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('update_leave_card');
    }

    public function delete(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('delete_leave_card');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_leave_card');
    }

    public function forceDelete(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('force_delete_leave_card');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_leave_card');
    }

    public function restore(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('restore_leave_card');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_leave_card');
    }

    public function replicate(User $user, LeaveCard $leaveCard): bool
    {
        return $user->can('replicate_leave_card');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_leave_card');
    }
}
