<?php

declare(strict_types=1);

namespace Zana\Mediator\Tests;

use Zana\Container\ScopeMetadata;
use Zana\Mediator\Attribute\Handles;
use Zana\Mediator\PipelineBehavior;

interface AuditableMessage
{
}

class CalculateBmi implements AuditableMessage
{
    /** @var list<string> */
    public array $trail = [];

    public function __construct(public readonly float $weightKg, public readonly float $heightM)
    {
    }
}

final class CalculateChildBmi extends CalculateBmi
{
}

final class Archive implements AuditableMessage
{
}

final class Ping
{
}

final class CalculateBmiHandler
{
    public function __invoke(CalculateBmi $command): float
    {
        $command->trail[] = 'handler';

        return round($command->weightKg / ($command->heightM ** 2), 1);
    }
}

#[Handles(Archive::class)]
#[Handles(Ping::class)]
final class MultiHandler
{
    public function handle(object $message): string
    {
        return $message::class;
    }
}

final class AuditHandler
{
    public function handle(AuditableMessage $message): string
    {
        return 'audited';
    }
}

final class UntypedHandler
{
    public function __invoke(mixed $message): void
    {
    }
}

final class NoMethodHandler
{
}

final class ScopedHandler
{
    public function __construct(public readonly ScopeMetadata $scope)
    {
    }

    public function __invoke(Ping $ping): string
    {
        return $this->scope->type;
    }
}

final class TraceBehavior implements PipelineBehavior
{
    public function __construct(private readonly string $name = 'trace')
    {
    }

    public function handle(object $message, \Closure $next): mixed
    {
        if ($message instanceof CalculateBmi) {
            $message->trail[] = "{$this->name}:in";
        }
        $result = $next($message);
        if ($message instanceof CalculateBmi) {
            $message->trail[] = "{$this->name}:out";
        }

        return $result;
    }
}

final class ShortCircuitBehavior implements PipelineBehavior
{
    public function handle(object $message, \Closure $next): mixed
    {
        return 'cached';
    }
}
