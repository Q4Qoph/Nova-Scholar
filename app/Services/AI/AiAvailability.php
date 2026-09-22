<?php

declare(strict_types=1);

namespace App\Services\AI;

class AiAvailability
{
    public function allowsProvider(AiExecutionContext $context): bool
    {
        return match ($context) {
            AiExecutionContext::Personal => true,
            AiExecutionContext::School, AiExecutionContext::ManagedLearner => (bool) config('ai.school_enabled', false),
        };
    }
}
