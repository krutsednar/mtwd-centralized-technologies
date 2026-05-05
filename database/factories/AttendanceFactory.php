<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profile = Profile::inRandomOrder()->first() ?? Profile::factory()->create();

        return [
            'profile_id' => $profile->id,
            'employee_number' => $profile->employee_number,
            'attendance_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'morning_in' => fake()->optional()->time('H:i:s', '08:30:00'),
            'morning_out' => fake()->optional()->time('H:i:s', '12:00:00'),
            'afternoon_in' => fake()->optional()->time('H:i:s', '13:00:00'),
            'afternoon_out' => fake()->optional()->time('H:i:s', '17:30:00'),
        ];
    }
}
