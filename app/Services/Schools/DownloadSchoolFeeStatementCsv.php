<?php

declare(strict_types=1);

namespace App\Services\Schools;

use App\Models\Enrolment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadSchoolFeeStatementCsv
{
    /**
     * @param  array<string, mixed>  $statement
     */
    public function handle(array $statement): StreamedResponse
    {
        return response()->streamDownload(function () use ($statement): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Statement reference', $statement['reference']], ',', '"', '');
            fputcsv($stream, ['School', $this->safeValue($statement['school']->name)], ',', '"', '');
            fputcsv($stream, ['Learner', $this->safeValue($this->learnerName($statement['enrolment']))], ',', '"', '');
            fputcsv($stream, ['Period from', $statement['from'] ?? 'Beginning'], ',', '"', '');
            fputcsv($stream, ['Period to', $statement['to'] ?? 'Present'], ',', '"', '');
            fputcsv($stream, ['Generated at', $statement['generated_at']->toIso8601String()], ',', '"', '');
            fputcsv($stream, [], ',', '"', '');

            foreach ($statement['groups'] as $group) {
                fputcsv($stream, ['Currency', $group['currency']], ',', '"', '');
                fputcsv($stream, ['Opening balance', $group['opening_minor']], ',', '"', '');
                fputcsv($stream, ['Date', 'Type', 'Description', 'Amount (minor units)', 'Running balance (minor units)'], ',', '"', '');

                foreach ($group['rows'] as $row) {
                    fputcsv($stream, [
                        $row['date'],
                        $row['type'],
                        $this->safeValue($row['description']),
                        $row['amount_minor'],
                        $row['running_balance_minor'],
                    ], ',', '"', '');
                }

                fputcsv($stream, ['Activity', $group['activity_minor']], ',', '"', '');
                fputcsv($stream, ['Closing balance', $group['closing_minor']], ',', '"', '');
                fputcsv($stream, [], ',', '"', '');
            }

            fclose($stream);
        }, 'fee-statement-'.$statement['reference'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function learnerName(Enrolment $enrolment): string
    {
        $profile = $enrolment->learnerProfile;

        return trim(($profile->preferred_name ?: $profile->first_name).' '.$profile->last_name);
    }

    private function safeValue(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
