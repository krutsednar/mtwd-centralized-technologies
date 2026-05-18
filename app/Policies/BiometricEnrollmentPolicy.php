<?php

namespace App\Policies;

use App\Models\BiometricEnrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BiometricEnrollmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_biometric_enrollment');
    }

    public function view(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('view_biometric_enrollment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_biometric_enrollment');
    }

    public function update(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('update_biometric_enrollment');
    }

    public function delete(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('delete_biometric_enrollment');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_biometric_enrollment');
    }

    public function forceDelete(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('force_delete_biometric_enrollment');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_biometric_enrollment');
    }

    public function restore(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('restore_biometric_enrollment');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_biometric_enrollment');
    }

    public function replicate(User $user, BiometricEnrollment $biometricEnrollment): bool
    {
        return $user->can('replicate_biometric_enrollment');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_biometric_enrollment');
    }
}
