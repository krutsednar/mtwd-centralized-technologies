<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Profile;
use App\Services\OnlineHrisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SyncOnlineAttendance implements ShouldQueue
{
    use Queueable;

    /** Maps local time field name → remote _synced flag name */
    private const FIELD_SYNC_MAP = [
        'morning_in' => 'am_in_synced',
        'morning_out' => 'am_out_synced',
        'afternoon_in' => 'pm_in_synced',
        'afternoon_out' => 'pm_out_synced',
        'ot_in' => 'ot_in_synced',
        'ot_out' => 'ot_out_synced',
    ];

    public function handle(): void
    {
        $service = app(OnlineHrisService::class);
        $records = $service->fetchUnsyncedAttendances();

        if ($records->isEmpty()) {
            Log::info('Online HRIS sync: No new attendance records to sync.');

            return;
        }

        $synced = 0;
        $profileMap = $this->buildProfileMap($records);

        foreach ($records as $record) {
            $profileId = $profileMap[$record['employee_number']] ?? null;

            if ($profileId === null) {
                Log::warning('Online HRIS sync: no local profile found for employee, skipping.', [
                    'employee_number' => $record['employee_number'],
                    'remote_id' => $record['id'],
                ]);

                continue;
            }

            try {
                $nonNullTimeFields = $this->extractNonNullTimeFields($record);

                if (empty($nonNullTimeFields)) {
                    Log::info('Online HRIS sync: record has no time values to sync, skipping.', [
                        'remote_id' => $record['id'],
                        'employee_number' => $record['employee_number'],
                    ]);

                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_number' => $record['employee_number'],
                        'attendance_date' => $record['attendance_date'],
                    ],
                    array_merge($nonNullTimeFields, [
                        'profile_id' => $profileId,
                        'remote_id' => $record['id'],
                        'is_synced' => true,
                        'synced_at' => now(),
                    ])
                );

                $service->markSynced($record['id'], array_keys($nonNullTimeFields));
                $synced++;
            } catch (\Throwable $e) {
                Log::error('Online HRIS sync: failed to process record.', [
                    'remote_id' => $record['id'] ?? null,
                    'employee_number' => $record['employee_number'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Online HRIS sync complete: {$synced} records saved and marked synced.");
    }

    private function buildProfileMap(Collection $records): array
    {
        $employeeNumbers = $records->pluck('employee_number')->unique()->values();

        return Profile::query()
            ->whereIn('employee_number', $employeeNumbers)
            ->pluck('id', 'employee_number')
            ->all();
    }

    private function extractNonNullTimeFields(array $record): array
    {
        $fields = [];

        foreach (array_keys(self::FIELD_SYNC_MAP) as $field) {
            $value = $record[$field] ?? null;

            if (filled($value)) {
                $fields[$field] = $value;
            }
        }

        return $fields;
    }
}
