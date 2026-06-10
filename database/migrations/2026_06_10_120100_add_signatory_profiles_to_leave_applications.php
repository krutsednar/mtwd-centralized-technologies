<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // 7.A — Certification of Leave Credits (two signatories)
            $table->foreignId('certification_hr_staff_profile_id')->nullable()->after('certification_leave_credits')->constrained('profiles')->nullOnDelete();
            $table->foreignId('certification_hr_chief_profile_id')->nullable()->after('certification_hr_staff_profile_id')->constrained('profiles')->nullOnDelete();

            // 7.B — Recommendation (applicant's division head)
            $table->foreignId('recommendation_signatory_profile_id')->nullable()->after('recommendation_disapproval_reason')->constrained('profiles')->nullOnDelete();

            // 7.C — Approved For (General Manager or designated signatory)
            $table->foreignId('approval_signatory_profile_id')->nullable()->after('approval_others_specify')->constrained('profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certification_hr_staff_profile_id');
            $table->dropConstrainedForeignId('certification_hr_chief_profile_id');
            $table->dropConstrainedForeignId('recommendation_signatory_profile_id');
            $table->dropConstrainedForeignId('approval_signatory_profile_id');
        });
    }
};
