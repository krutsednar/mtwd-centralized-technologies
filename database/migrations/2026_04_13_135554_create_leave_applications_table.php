<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->date('date_of_filing')->default(now());
            $table->string('position')->nullable();
            $table->decimal('salary', 10, 2)->nullable();

            // 6. Details of Application
            $table->string('leave_type');

            // 6.A Location details (vacation / special leave)
            $table->string('details_location')->nullable();          // within_philippines | abroad
            $table->string('details_location_specific')->nullable();

            // 6.B Specific leave type details
            $table->string('details_sick_leave')->nullable();        // in_hospital | out_patient
            $table->text('details_sick_leave_specific')->nullable();
            $table->text('details_special_benefits_women')->nullable();
            $table->string('details_study_leave')->nullable();       // completion_masters | bar_board_review | other
            $table->string('details_other_purpose')->nullable();     // monetization | terminal_leave

            // 6.C Inclusive dates
            $table->date('from');
            $table->date('to');
            $table->integer('days_applied_number')->default(1);

            // 6.D Commutation
            $table->string('commutation')->default('not_requested'); // requested | not_requested

            // 7.A Certification of Leave Credits
            $table->json('certification_leave_credits')->nullable();

            // 7.B Recommendation
            $table->string('recommendation')->nullable();            // for_approval | for_disapproval
            $table->text('recommendation_disapproval_reason')->nullable();

            // 7.C/D Approval
            $table->string('approval_status')->nullable();           // with_pay | without_pay | others | disapproved
            $table->string('approval_others_specify')->nullable();

            // Signatories
            $table->string('authorized_officer_certification')->nullable();
            $table->string('authorized_official_approval')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
