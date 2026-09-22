<?php

namespace App\Services\Schools;

use App\Models\Enrolment;
use App\Models\ImportBatch;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StageLearnerImport
{
    private const HEADERS = ['first_name', 'last_name', 'preferred_name', 'date_of_birth', 'admission_number'];

    public function handle(User $actor, School $school, UploadedFile $file): ImportBatch
    {
        $path = $file->getRealPath();
        $checksum = is_string($path) ? hash_file('sha256', $path) : false;

        if ($path === false || $checksum === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV could not be read.']);
        }

        $existingBatch = $school->importBatches()->where('source_checksum', $checksum)->first();
        if ($existingBatch !== null) {
            return $existingBatch;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV could not be opened.']);
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers) || $this->normaliseHeaders($headers) !== self::HEADERS) {
            fclose($handle);

            throw ValidationException::withMessages([
                'file' => 'The CSV header must be: first_name,last_name,preferred_name,date_of_birth,admission_number.',
            ]);
        }

        $rows = [];
        $seenAdmissions = [];
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $values = array_map(static fn ($value): ?string => is_string($value) ? trim($value) : null, $values);

            if ($this->isBlankRow($values)) {
                continue;
            }

            $paddedValues = array_pad($values, count(self::HEADERS), null);
            $payload = array_combine(self::HEADERS, array_slice($paddedValues, 0, count(self::HEADERS)));
            $errors = [];

            if (count($values) !== count(self::HEADERS)) {
                $errors['row'][] = 'This row must contain exactly five columns.';
            }

            $validator = Validator::make($payload, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'preferred_name' => ['nullable', 'string', 'max:100'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'admission_number' => ['required', 'string', 'max:50'],
            ]);
            $errors = array_merge($errors, $validator->errors()->toArray());

            $admissionNumber = $payload['admission_number'] ?? null;
            if (is_string($admissionNumber) && $admissionNumber !== '') {
                if (isset($seenAdmissions[$admissionNumber])) {
                    $errors['admission_number'][] = 'This admission number is duplicated in the upload.';
                }

                if (Enrolment::query()->where('school_id', $school->id)->where('admission_number', $admissionNumber)->exists()) {
                    $errors['admission_number'][] = 'This admission number already exists in the school.';
                }

                $seenAdmissions[$admissionNumber] = true;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'payload' => $payload,
                'validation_errors' => $errors === [] ? null : $errors,
                'status' => $errors === [] ? 'valid' : 'invalid',
            ];
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The CSV does not contain any learner rows.']);
        }

        return DB::transaction(function () use ($actor, $school, $file, $checksum, $rows): ImportBatch {
            $batch = $school->importBatches()->create([
                'uploaded_by_user_id' => $actor->id,
                'source_filename' => $file->getClientOriginalName(),
                'source_checksum' => $checksum,
                'status' => 'staged',
                'total_rows' => count($rows),
                'valid_rows' => collect($rows)->where('status', 'valid')->count(),
                'invalid_rows' => collect($rows)->where('status', 'invalid')->count(),
            ]);

            $batch->rows()->createMany($rows);
            $school->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'event_type' => 'learner_import.staged',
                'auditable_type' => ImportBatch::class,
                'auditable_id' => $batch->id,
                'metadata' => [
                    'total_rows' => $batch->total_rows,
                    'valid_rows' => $batch->valid_rows,
                    'invalid_rows' => $batch->invalid_rows,
                ],
                'occurred_at' => now(),
            ]);

            return $batch;
        });
    }

    /** @param  array<int, mixed>  $headers */
    private function normaliseHeaders(array $headers): array
    {
        $headers[0] = is_string($headers[0] ?? null) ? preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) : ($headers[0] ?? null);

        return array_map(static fn ($header): string => trim((string) $header), $headers);
    }

    /** @param  array<int, ?string>  $values */
    private function isBlankRow(array $values): bool
    {
        return collect($values)->every(static fn (?string $value): bool => $value === null || $value === '');
    }
}
