<?php

declare(strict_types=1);

namespace Zana\Mediator;

/**
 * Sends a command or query to its single handler and returns the handler's result.
 */
interface MediatorInterface
{
    /**
     * @throws HandlerNotFoundException If no handler is mapped for the message
     */
    public function send(object $message): mixed;
}
