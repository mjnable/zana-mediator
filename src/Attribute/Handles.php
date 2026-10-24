<?php

declare(strict_types=1);

namespace Zana\Mediator\Attribute;

use Attribute;

/**
 * Declare the message class a handler handles (repeatable).
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Handles
{
    /**
     * @param class-string $message
     */
    public function __construct(public readonly string $message)
    {
    }
}
