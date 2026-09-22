<?php

declare(strict_types=1);

namespace App\Services\AI;

enum AiExecutionContext: string
{
    case Personal = 'personal';
    case School = 'school';
    case ManagedLearner = 'managed_learner';
}
