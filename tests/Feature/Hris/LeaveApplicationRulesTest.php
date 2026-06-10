<?php

namespace Tests\Feature\Hris;

use App\Models\LeaveApplication;

/*
 * Pure rule logic for the CSC leave alignment — no DB (the project's migrations
 * are Postgres-only). June 2026 reference weekdays: 8=Mon, 9=Tue, 10=Wed,
 * 11=Thu, 12=Fri, 13=Sat, 14=Sun, 15=Mon, 16=Tue.
 */

it('routes managerial designations to the General Manager', function (string $position, bool $expected) {
    expect(LeaveApplication::isManagerialPosition($position))->toBe($expected);
})->with([
    ['Department Manager', true],
    ['Acting Division Manager', true],
    ['OIC Assistant General Manager', true],
    ['Department Manager A', true],          // substring match tolerates plantilla suffixes
    ['division manager', true],              // case-insensitive
    ['Senior Engineer B', false],
    ['Cashier', false],
    ['Administrative Officer V', false],
]);

it('treats a blank position as non-managerial', function () {
    expect(LeaveApplication::isManagerialPosition(null))->toBeFalse()
        ->and(LeaveApplication::isManagerialPosition(''))->toBeFalse();
});

it('counts the longest run of consecutive working days', function () {
    // Tue–Thu = 3 consecutive working days.
    expect(LeaveApplication::maxConsecutiveWorkingDays(['2026-06-09', '2026-06-10', '2026-06-11']))->toBe(3)
        // Tue–Fri = 4.
        ->and(LeaveApplication::maxConsecutiveWorkingDays(['2026-06-09', '2026-06-10', '2026-06-11', '2026-06-12']))->toBe(4)
        // Fri + Mon (weekend skipped) = 2 consecutive working days.
        ->and(LeaveApplication::maxConsecutiveWorkingDays(['2026-06-12', '2026-06-15']))->toBe(2);
});

it('breaks a run when a working day in between is not selected', function () {
    // Tue 9 + Thu 11, with Wed 10 a working day that was skipped → two separate runs.
    expect(LeaveApplication::maxConsecutiveWorkingDays(['2026-06-09', '2026-06-11']))->toBe(1);
});

it('skips a holiday so the surrounding working days stay consecutive', function () {
    // Wed 10 is a holiday → Tue 9 and Thu 11 are consecutive working days.
    expect(LeaveApplication::maxConsecutiveWorkingDays(['2026-06-09', '2026-06-11'], ['2026-06-10']))->toBe(2);
});

it('rejects a Wellness Leave date that falls on a Monday', function () {
    $errors = LeaveApplication::wellnessValidationErrors(['2026-06-15']); // Monday

    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('Monday');
});

it('rejects more than 3 consecutive working days of Wellness Leave', function () {
    $errors = LeaveApplication::wellnessValidationErrors(['2026-06-09', '2026-06-10', '2026-06-11', '2026-06-12']);

    expect(collect($errors)->contains(fn ($e) => str_contains($e, 'consecutive')))->toBeTrue();
});

it('rejects the 6th Wellness Leave day in a calendar year', function () {
    // 3 already used + 3 applied for = 6 > 5.
    $errors = LeaveApplication::wellnessValidationErrors(['2026-06-09', '2026-06-10', '2026-06-11'], 3);

    expect(collect($errors)->contains(fn ($e) => str_contains($e, 'per calendar year')))->toBeTrue();
});

it('accepts a valid Wellness Leave application', function () {
    // Tue–Thu, not a Monday, 3 consecutive working days, 0 used this year.
    $errors = LeaveApplication::wellnessValidationErrors(['2026-06-09', '2026-06-10', '2026-06-11'], 0);

    expect($errors)->toBeEmpty();
});

// ── CS Form No. 6 page-2 documentary requirements ──

it('exposes documentary requirements per leave type', function () {
    expect(LeaveApplication::requirementsFor('sick')['documents'])->not->toBeEmpty()
        ->and(LeaveApplication::requirementsFor('adoption')['documents'][0])->toContain('DSWD')
        ->and(LeaveApplication::requirementsFor('solo_parent')['documents'][0])->toContain('Solo Parent')
        ->and(LeaveApplication::requirementsFor('maternity')['documents'][0])->toContain('pregnancy');
});

it('requires supporting documents only for attachment-based leave types', function () {
    expect(LeaveApplication::requiresSupportingDocuments('sick'))->toBeTrue()
        ->and(LeaveApplication::requiresSupportingDocuments('adoption'))->toBeTrue()
        ->and(LeaveApplication::requiresSupportingDocuments('others', 'monetization'))->toBeTrue()
        ->and(LeaveApplication::requiresSupportingDocuments('vacation'))->toBeFalse()
        ->and(LeaveApplication::requiresSupportingDocuments('special_privilege'))->toBeFalse()
        ->and(LeaveApplication::requiresSupportingDocuments('mandatory_forced'))->toBeFalse()
        ->and(LeaveApplication::requiresSupportingDocuments('wellness'))->toBeFalse()
        ->and(LeaveApplication::requiresSupportingDocuments('others'))->toBeFalse();
});

it('merges 6.B other-purpose documentary requirements', function () {
    $monetization = LeaveApplication::requirementsFor('others', 'monetization')['documents'];
    $terminal = LeaveApplication::requirementsFor('others', 'terminal_leave')['documents'];

    expect(collect($monetization)->contains(fn ($d) => str_contains($d, 'Letter request')))->toBeTrue()
        ->and(collect($terminal)->contains(fn ($d) => str_contains($d, 'separation')))->toBeTrue();
});

it('flags clearance for 30+ day or terminal leave', function () {
    expect((new LeaveApplication(['leave_type' => 'vacation', 'days_applied_number' => 30]))->requires_clearance)->toBeTrue()
        ->and((new LeaveApplication(['leave_type' => 'others', 'details_other_purpose' => 'terminal_leave', 'days_applied_number' => 1]))->requires_clearance)->toBeTrue()
        ->and((new LeaveApplication(['leave_type' => 'vacation', 'days_applied_number' => 3]))->requires_clearance)->toBeFalse();
});
