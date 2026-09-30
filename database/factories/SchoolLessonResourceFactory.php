<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolLessonResource;
use App\Models\SchoolLessonVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolLessonResource>
 */
class SchoolLessonResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'school_lesson_version_id' => SchoolLessonVersion::factory(),
            'uploaded_by_user_id' => User::factory(),
            'display_name' => fake()->word().'.pdf',
            'storage_disk' => 'local',
            'storage_key' => fake()->uuid().'.pdf',
            'media_type' => 'application/pdf',
            'byte_size' => 1024,
            'sha256' => fake()->sha256(),
            'status' => 'scan_pending',
            'rights_basis' => 'educator_created',
            'rights_reference' => null,
            'rights_attested_at' => now(),
            'validated_at' => now(),
            'scanned_at' => null,
            'scanner_name' => null,
            'scanner_signature_version' => null,
            'scan_result_code' => null,
            'purge_after' => null,
            'bytes_purged_at' => null,
        ];
    }
}
