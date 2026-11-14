<?php

declare(strict_types=1);

namespace Zana\Mediator;

/**
 * Sends a command or query to its single handler and returns the handler's result.
 */
interface MediatorInterface
{
    /**
     * Any exception the handler or a pipeline behavior throws reaches the
     * caller as it is (2.20, FE-77: it is declared, so a catch of a
     * handler's domain exception is not reported as dead).
     *
     * @throws HandlerNotFoundException If no handler is mapped for the message
     * @throws \Throwable What the handler or a behavior throws
     */
    public function send(object $message): mixed;
}
