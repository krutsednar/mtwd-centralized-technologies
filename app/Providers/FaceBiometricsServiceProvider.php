<?php

namespace App\Providers;

use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use Illuminate\Support\ServiceProvider;

class FaceBiometricsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add faceProfile relation to Profile at runtime — Profile.php is not modified.
        Profile::resolveRelationUsing('faceProfile', function (Profile $profile) {
            return $profile->hasOne(FaceProfile::class);
        });
    }

    public function register(): void
    {
        //
    }
}
