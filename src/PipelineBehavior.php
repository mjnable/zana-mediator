<?php

declare(strict_types=1);

namespace Zana\Mediator;

/**
 * Wraps message handling, e.g. for validation, transactions or logging.
 * Not calling $next short-circuits the handler.
 */
interface PipelineBehavior
{
    /**
     * @param \Closure(object): mixed $next
     */
    public function handle(object $message, \Closure $next): mixed;
}
