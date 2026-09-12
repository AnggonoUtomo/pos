<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Infrastructure\Events;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceActivityPublisher;
use App\Modules\System\AccessControl\Application\Events\SystemActivityOccurred;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

final readonly class LaravelHumanResourceActivityPublisher implements HumanResourceActivityPublisher
{
    public function __construct(
        private DatabaseManager $database,
        private Dispatcher $events,
    ) {}

    public function publish(
        ?string $actorId,
        string $action,
        string $subjectType,
        Closure $mutation,
        Closure $subjectId,
        Closure $metadata,
        ?string $reason = null,
        ?string $correlationId = null,
    ): mixed {
        return $this->database->connection()->transaction(function () use (
            $actorId,
            $action,
            $subjectType,
            $mutation,
            $subjectId,
            $metadata,
            $reason,
            $correlationId,
        ): mixed {
            $result = $mutation();

            $this->events->dispatch(new SystemActivityOccurred(
                module: 'HumanResource',
                eventName: 'human-resource.activity.occurred',
                version: 1,
                eventId: (string) Str::ulid(),
                occurredAt: now()->toIso8601String(),
                correlationId: $correlationId ?? (string) Str::ulid(),
                actorId: $actorId,
                action: $action,
                subjectType: $subjectType,
                subjectId: $subjectId($result),
                reason: $reason,
                metadata: $metadata($result),
            ));

            return $result;
        });
    }
}
