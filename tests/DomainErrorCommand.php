<?php

declare(strict_types=1);

namespace Zana\Mediator\Tests;

use Zana\Mediator\MediatorInterface;

/**
 * FE-77: a handler's domain exception caught around send(), as a console
 * command does. PHPStan analyses this file with the package, so if send()'s
 * annotations made this catch look dead, the build fails, not an application's.
 */
final readonly class DomainErrorCommand
{
    public function __construct(private MediatorInterface $mediator)
    {
    }

    public function run(object $command): string
    {
        try {
            $this->mediator->send($command);
        } catch (\DomainException $e) {
            return $e->getMessage();
        }

        return 'done';
    }
}
