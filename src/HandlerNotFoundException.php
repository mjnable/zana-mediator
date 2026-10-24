<?php

declare(strict_types=1);

namespace Zana\Mediator;

final class HandlerNotFoundException extends MediatorException
{
    public static function for(object $message): self
    {
        return new self('No handler is mapped for ' . $message::class . '. Map one with HandlerMap::register() or map().');
    }
}
