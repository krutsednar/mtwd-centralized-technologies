<?php

namespace App\ValueObjects\FaceBiometrics;

use App\Models\Profile;

class VerifyResult
{
    public function __construct(
        public readonly bool $matched,
        public readonly ?Profile $profile,
        public readonly float $score,
        public readonly float $secondScore,
        public readonly float $margin,
        public readonly float $liveness,
        public readonly float $quality,
        public readonly string $reason,
    ) {}
}
