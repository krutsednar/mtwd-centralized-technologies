# Prompt: Align the Leave Application module with CS Form No. 6 (Revised 2020) + MTWD rules

You are working in the `mtwd-centralized-technologies` Laravel 12 / Filament 3 app
(Postgres, schema `mct_proddb`). Follow `CLAUDE.md`: use `php artisan make:*`,
run `vendor/bin/pint --dirty` before finishing, write Pest tests, prefer Eloquent
relationships, and don't add Composer dependencies without asking. Build front-end
changes are server-rendered Filament/Blade (no JS bundle needed).

> ⚠️ Test DB caveat: this project's migrations are Postgres-coupled (pgvector
> `vector(512)`, `SET search_path TO mct_proddb`), so the sqlite `:memory:` test
> DB cannot run the full migration set. Unit-test pure logic (validation rules,
> signatory resolution) in isolation; validate DB-touching pieces against the
> real Postgres DB with a read-only tinker script.

---

## 1. Current state (already implemented — do not rebuild from scratch)

- **Model** `app/Models/LeaveApplication.php` — maps CS Form No. 6 sections 6.A–6.D
  and 7.A–7.D. Constants: `LEAVE_TYPE_SELECT`, `LOCATION_SELECT`,
  `SICK_LEAVE_SELECT`, `STUDY_LEAVE_SELECT`, `OTHER_PURPOSE_SELECT`,
  `RANGE_BASED_LEAVE_TYPES`, `COMMUTATION_SELECT`, `RECOMMENDATION_SELECT`,
  `APPROVAL_STATUS_SELECT`. Auto-numbers `leave_application_no` on create.
- **Migration** `database/migrations/2026_04_13_135554_create_leave_applications_table.php`.
- **Inclusive dates** `app/Models/LeaveInclusiveDates.php` (hasMany; per-date `duration`
  0.5/1). Range-based types (maternity/study/rehabilitation/special_women) use `from`/`to`.
- **Resource** `app/Filament/Hris/Resources/LeaveApplicationResource.php` — a 3-step
  Wizard: *Application Details* → *Action on Application* → *Final Approval*.
  - Position & salary auto-fill from the latest `ServiceRecord` of the chosen employee.
  - Signatories today are **two free-text inputs**:
    `authorized_officer_certification` (7.A) and `authorized_official_approval` (7.C).
  - 7.B has a recommendation radio + disapproval reason but **no signatory**.
- **Print** `resources/views/leave-print.blade.php` rendered by
  `app/Http/Controllers/Hris/Leave/PrintLeaveFormController.php` (route name
  `leave.print`). It has **no MTWD logo header**, 7.A has one signatory, 7.B none,
  7.C one free-text signatory.

### Building blocks you will reuse
- **Org / signatory model** `app/Models/Division.php`:
  - `head_profile_id`, `oic_profile_id`, `oic_active` (boolean).
  - `getActiveSignatory(): ?Profile` → returns the OIC when `oic_active`, else the head.
  - `signatureChain()` walks ancestors → root.
  - Org types: `TYPE_OGM` (Office of the General Manager), `TYPE_OAGM` (Office of the
    AGM), `TYPE_ODM` (Office of the Department Manager), `TYPE_DIVISION`, `TYPE_SECTION`.
  - Managed by `app/Filament/Hris/Resources/SupervisorManagementResource.php`
    ("Supervisor Management"), which assigns head / OIC per org unit.
- **Employee → division**: `Profile::division()` (belongsTo). `Profile::full_name`.
- **Position source**: latest `ServiceRecord` (`position`, `salary`). The applicant's
  position string is copied to `LeaveApplication.position`.
- **Holidays** `app/Models/Holiday.php::nonWorkingDates(): array` → `Y-m-d` strings of
  regular + special-non-working holidays. Use for "working day" math.
- **Print header reference**: `resources/views/pdf/service-record-header.blade.php`
  (the user said "service-records-table" but the real logo header is here). It uses
  `public_path('images/MTWD-Logo.png')` (left) and
  `public_path('images/Bagong-Pilipinas-Logo.png')` (right), agency name centered, a
  blue (`#003399`) + red (`#cc0000`) separator bar, then the document title. Both
  images exist in `public/images/`.

---

## 2. CS Form No. 6 (Revised 2020) — canonical structure to match

1. Office/Department · 2. Name (Last, First, MI) · 3. Date of Filing · 4. Position · 5. Salary.
6. **Details of Application**
   - 6.A Type of leave to be availed of (checklist, each with its legal basis).
   - 6.B Details of leave (location for VL/SPL; in-hospital/out-patient + illness for SL;
     gynecological illness for Special Leave for Women; study purpose; "Other purpose:
     Monetization of Leave Credits / Terminal Leave").
   - 6.C Number of working days applied for + inclusive dates.
   - 6.D Commutation (Requested / Not Requested).
7. **Details of Action on Application**
   - 7.A Certification of Leave Credits (as of date; total earned, less this application,
     balance — for VL & SL) + certifying officer.
   - 7.B Recommendation (For approval / For disapproval due to ___) + recommending officer.
   - 7.C Approved for: ___ days with pay / ___ days without pay / ___ others + approving official.
   - 7.D Disapproved due to ___ .

The existing module already covers the field structure. The work below is the **MTWD
customizations** layered on top.

---

## 3. Wellness Leave (new leave type)

Add a new leave type `wellness` → `Wellness Leave` to
`LeaveApplication::LEAVE_TYPE_SELECT`. It is an **inclusive-dates** type (NOT in
`RANGE_BASED_LEAVE_TYPES`). Enforce these rules in BOTH the Filament form (live, with
clear validation messages) and a reusable validation layer (a `FormRequest` or a
dedicated `WellnessLeaveRule` / model-level guard) so the API/programmatic path is
covered too:

1. **No Mondays** — no inclusive date may fall on a Monday (`Carbon::isMonday()`).
2. **Max 3 consecutive working days** — within a single application, the selected dates
   must not contain a run of more than 3 consecutive *working days*. A working day =
   weekday (Mon–Fri) that is not in `Holiday::nonWorkingDates()`. (Compute runs by
   walking the sorted dates; weekends/holidays break a run.)
3. **Max 5 per year** — the employee's total Wellness Leave **days** in the same
   calendar year (existing approved/recorded wellness applications + this one) must not
   exceed **5**.

> CONFIRMED BY USER: "max allowed is 5" = **5 Wellness Leave days per calendar year per
> employee** (rule #3 — existing approved/recorded wellness days in the year count toward
> the cap). "max if consecutive is 3 working days" = **at most 3 consecutive working days
> per application** (rule #2).

Put the managerial/wellness constants and helpers on the model (e.g.
`LeaveApplication::WELLNESS_MAX_DAYS_PER_YEAR = 5`,
`WELLNESS_MAX_CONSECUTIVE_WORKING_DAYS = 3`). Add a Pest unit test covering: a Monday
date is rejected; 4 consecutive working days rejected but 3 allowed; the 6th annual day
rejected; a holiday/weekend correctly breaks the consecutive run.

---

## 4. Signatories — restructure sections 7.A / 7.B / 7.C

Replace the two free-text signatory fields with **resolved, profile-linked signatories**
so the printed form is reproducible and auditable. Store a `*_profile_id` (nullable FK to
`profiles`) for each signatory role, auto-resolved when the application is created/edited
but overridable by HR. Snapshot the signatory's **name + role label** at print time from
the linked profile (and keep the legacy text columns only if you migrate their data).

### 7.A — Certification of Leave Credits → **two** signatories
- **Signatory 1 — Designated HR Employee who manages leave.** Comes from HRIS
  Configuration (see §5): `leave.hr_leave_administrator_profile_id`.
- **Signatory 2 — HR Division Chief.** The active signatory
  (`Division::getActiveSignatory()`) of the HR org unit. Resolve the HR unit from HRIS
  Configuration (`leave.hr_division_id`) — do not hard-code a name.
- New columns: `certification_hr_staff_profile_id`, `certification_hr_chief_profile_id`.

### 7.B — Recommendation → the applicant's **division head**
- Signatory = the applicant's `profile->division->getActiveSignatory()`, i.e. the
  **Division Chief**, or **Acting Division Chief** / **Officer-in-Charge** when an OIC is
  active. The printed role label must reflect which one:
  - `oic_active` && OIC set → "Officer-in-Charge".
  - else → the head's designation (Division Chief). If you need to distinguish a
    permanent vs "Acting" chief, add an optional `acting` boolean to the division head
    assignment (Supervisor Management) and label accordingly; otherwise default to
    "Division Chief".
- New column: `recommendation_signatory_profile_id`.

### 7.C — Approved For → **General Manager OR Designated Signatory**
- Determine from the applicant's position/designation
  (`LeaveApplication.position`, sourced from `ServiceRecord.position`):
  - If the position matches a **managerial designation**, the signatory is the
    **General Manager** = active signatory of the `TYPE_OGM` division
    (`Division::where('type', Division::TYPE_OGM)->first()?->getActiveSignatory()`).
  - Otherwise, the signatory is the **Designated Signatory** configured in HRIS
    Configuration (`leave.designated_approver_profile_id`); display that person's name on
    the form.
- Managerial designations that route to the GM (case-insensitive match; expose as a
  constant `LeaveApplication::GM_APPROVAL_DESIGNATIONS`):
  Division Manager, Acting Division Manager, OIC Division Manager,
  Department Manager, Acting Department Manager, OIC Department Manager,
  Assistant General Manager, Acting Assistant General Manager, OIC Assistant General Manager.
- New column: `approval_signatory_profile_id`.

Add `belongsTo` relations on `LeaveApplication` for each new signatory FK, plus a
`resolveSignatories()` helper (pure where possible) that computes the three roles from a
`Profile` + the HRIS Configuration, unit-tested in isolation. Auto-fill the signatory
fields in the resource (read-only displays of the resolved names, with an HR override).

---

## 5. HRIS Configuration — designated signatories (reusable for future forms)

There is **no settings mechanism yet**. Build a lightweight, reusable one (do NOT add a
Composer package without approval):

- Migration + model `app/Models/HrSetting.php` for a key/value store:
  `hr_settings(id, key unique, value jsonb nullable, timestamps)` with
  `HrSetting::get(string $key, $default = null)` and `HrSetting::set(string $key, $value)`
  helpers (cache-backed is fine).
- A Filament **custom Page** `app/Filament/Hris/Pages/HrisConfiguration.php` (navigation
  group e.g. "Configuration", in the HRIS panel) with a form that persists these keys:
  - `leave.designated_approver_profile_id` — Designated Signatory for 7.C when the
    applicant is non-managerial. (Profile select.)
  - `leave.hr_leave_administrator_profile_id` — 7.A signatory 1. (Profile select.)
  - `leave.hr_division_id` — the HR org unit whose active signatory is the 7.A
    signatory 2 (HR Division Chief). (Division select.)
- Design the page/keys so the **same pattern extends to future forms** — namespace keys
  by form: `cto.designated_approver_profile_id`, `ob_slip.designated_approver_profile_id`,
  `pass_slip.designated_approver_profile_id`, etc. Group the page by form section so new
  forms are a copy-paste of a section. Gate the page with a policy/permission so only HR
  admins can edit it.

---

## 6. Schema / model / migration changes (summary)

Create new migrations (never edit shipped ones). On `leave_applications` add nullable:
`certification_hr_staff_profile_id`, `certification_hr_chief_profile_id`,
`recommendation_signatory_profile_id`, `approval_signatory_profile_id` (all
`foreignId(...)->nullable()->constrained('profiles')->nullOnDelete()`). Decide whether to
keep or migrate the legacy `authorized_officer_certification` /
`authorized_official_approval` text columns (prefer: migrate any data into the new FKs,
then drop in a later migration). Add the new keys to `$fillable`/`casts`, the leave-type
constant, the wellness constants, the `GM_APPROVAL_DESIGNATIONS` constant, the new
`belongsTo` relations, and `resolveSignatories()`.

New tables: `hr_settings` (§5).

---

## 7. Printable form (`resources/views/leave-print.blade.php`)

Use the attached **CS Form No. 6, Revised 2020** as the visual basis and bring the print
output in line with it:

- **Add the MTWD logo header** at the top, mirroring
  `resources/views/pdf/service-record-header.blade.php`: MTWD logo (left), agency block
  centered ("Republic of the Philippines / METROPOLITAN TUGUEGARAO WATER DISTRICT /
  address / contact / website"), Bagong Pilipinas logo (right), blue + red separator bar,
  then title **"APPLICATION FOR LEAVE"** and subtitle **"CS Form No. 6, Revised 2020"**.
  - ⚠️ `leave-print.blade.php` is an HTML page printed via `window.print()` (not
    wkhtmltopdf), so reference the logos with **`asset('images/MTWD-Logo.png')`** /
    `asset('images/Bagong-Pilipinas-Logo.png')`, NOT the `file:///` form used in the
    wkhtmltopdf header.
- Render the full 6.A leave-type checklist **including Wellness Leave**, with the standard
  legal bases as small print.
- Render the three signatory blocks with the new resolved signatories:
  - 7.A: two signature lines — *Designated HR Employee (leave)* and *HR Division Chief*.
  - 7.B: one signature line labeled per role (Division Chief / Acting Division Chief /
    Officer-in-Charge).
  - 7.C: one signature line — *General Manager* or the *Designated Signatory* name.
- Keep the existing print/back bar (hidden in `@media print`).

---

## 8. Home panel — employee self-service Leave Application (fill up to 6.D only)

Employees file their **own** leave from the **Home panel** (the self-service panel,
`app/Providers/Filament/HomePanelProvider.php`, path `/home`). That panel already
auto-discovers resources from `app_path('Filament/Home/Resources')` (it currently has
only Pages), so a new resource dropped there is picked up automatically.

- **New resource** `app/Filament/Home/Resources/LeaveApplicationResource.php` (namespace
  `App\Filament\Home\Resources`), model `LeaveApplication`. Navigation e.g. group
  "Leave / CTO" or "My Records", label "Apply for Leave".
- **Scope to the signed-in employee.** Resolve the employee the same way existing home
  components do (see `app/Livewire/Home/LeaveCardTable.php`):
  `Profile::where('employee_number', auth()->user()->employee_number)->first()`.
  - `getEloquentQuery()` MUST filter `->where('profile_id', $profile->id)` so an employee
    only ever sees their own applications.
  - On create, force `profile_id` to the current employee — **no Employee picker** (unlike
    the HRIS resource). Auto-fill position/salary from their latest `ServiceRecord`
    (read-only) and department from their division.
- **Form = sections 1–6.D ONLY.** Reuse the *Application Details* content from the HRIS
  `LeaveApplicationResource::getFormSteps()` (leave type, 6.A–6.D, inclusive dates / range,
  commutation, plus the Wellness Leave rules from §3). **Do NOT include** the
  *Action on Application* (7.A/7.B) or *Final Approval* (7.C/7.D) steps — those are HR/
  management only, in the HRIS panel. Cleanest implementation: extract the shared
  "Application Details" schema into one reusable method/class (e.g.
  `App\Filament\Hris\Resources\LeaveApplicationResource::applicationDetailsSchema()` or a
  dedicated `LeaveApplicationForm`) that BOTH the HRIS resource (as wizard step 1) and the
  Home resource consume, so the two never drift.
- **Workflow handoff.** A submitted application keeps `approval_status = null` (the HRIS
  table already renders null as "Pending"). It then appears in the HRIS
  `LeaveApplicationResource` for HR to fill 7.A–7.D and resolve signatories (§4).
- **Edit / withdraw limits.** An employee may edit or delete their own application **only
  while it is still pending** — i.e. before HR has entered any 7.A certification, 7.B
  recommendation, or 7.C approval. After HR acts, it is read-only to the employee. Enforce
  via the resource `canEdit`/`canDelete` and/or `LeaveApplicationPolicy`.
- **Read-only result view.** Give employees a View/infolist that shows the 7.A–7.D outcome
  (certified credits, recommendation, approval status, resolved signatory names) once HR
  has processed it — visible but not editable.
- **Authorization.** Extend `app/Policies/LeaveApplicationPolicy.php` so Home-panel users
  are limited to their own records while the HRIS panel (HR) keeps full access.

---

## 9. Acceptance criteria

- Wellness Leave appears in the form and print; its three rules are enforced with clear
  messages and covered by Pest unit tests.
- Saving a leave application auto-resolves all four signatory roles from the employee's
  division + HRIS Configuration, editable by HR; the print shows the correct names/roles.
- 7.C routes to the GM for managerial designations and to the configured Designated
  Signatory otherwise (unit-tested).
- HRIS Configuration page persists the designated-signatory settings and is permission-
  gated; key naming supports CTO / OB Slip / Pass Slip without redesign.
- Print output carries the MTWD logo header and matches CS Form No. 6 layout.
- Employees can file a leave application from the **Home panel** filling only sections
  1–6.D, auto-scoped to their own profile; it surfaces in the HRIS panel as *Pending* for
  HR to complete 7.A–7.D. Employees cannot view or edit other employees' applications, and
  cannot edit once HR has acted.
- `vendor/bin/pint` clean; relevant tests pass.

---

## 10. Related (optional) — leave_cards NOT NULL bug

Separate from the leave-application module, the user hit this at `/hris/leave-cards/create`:

```
SQLSTATE[23502]: null value in column "vl_without_pay" of relation "leave_cards"
violates not-null constraint
```

When creating a "Beg Bal" leave card, `vl_without_pay` / `sl_without_pay` are null but the
columns are NOT NULL. Fix by either defaulting those columns to `0` (new migration:
`->default(0)` / backfill) or always supplying `0` in
`app/Filament/Hris/Resources/LeaveCardResource.php` create flow. Confirm the full set of
NOT-NULL numeric columns on `leave_cards` and apply consistently.
