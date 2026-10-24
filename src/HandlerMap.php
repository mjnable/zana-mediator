<?php

declare(strict_types=1);

namespace Zana\Mediator;

use Zana\Mediator\Attribute\Handles;

/**
 * Maps message classes to their single handler class.
 */
final class HandlerMap
{
    /** @var array<class-string, class-string> message => handler */
    private array $handlers = [];

    /**
     * @param class-string $message
     * @param class-string $handler
     * @throws MediatorException If the message already has a handler
     */
    public function map(string $message, string $handler): self
    {
        if (isset($this->handlers[$message]) && $this->handlers[$message] !== $handler) {
            throw new MediatorException("{$message} is already handled by {$this->handlers[$message]}; it cannot also be handled by {$handler}.");
        }

        $this->handlers[$message] = $handler;

        return $this;
    }

    /**
     * Map handlers from #[Handles], or from the type of the first parameter of __invoke or handle.
     *
     * @param class-string ...$handlers
     */
    public function register(string ...$handlers): self
    {
        foreach ($handlers as $handler) {
            $class = new \ReflectionClass($handler);
            $attributes = $class->getAttributes(Handles::class);

            if ($attributes !== []) {
                foreach ($attributes as $attribute) {
                    $this->map($attribute->newInstance()->message, $handler);
                }
                continue;
            }

            $this->map(self::inferMessage($class), $handler);
        }

        return $this;
    }

    /**
     * The handler for a message: its exact class, then parents (nearest first), then interfaces.
     *
     * @return class-string|null
     */
    public function handlerFor(object $message): ?string
    {
        $candidates = [$message::class, ...array_values(class_parents($message) ?: []), ...array_values(class_implements($message) ?: [])];

        foreach ($candidates as $candidate) {
            if (isset($this->handlers[$candidate])) {
                return $this->handlers[$candidate];
            }
        }

        return null;
    }

    /**
     * @return array<class-string, class-string>
     */
    public function all(): array
    {
        return $this->handlers;
    }

    /**
     * @param \ReflectionClass<object> $class
     * @return class-string
     */
    private static function inferMessage(\ReflectionClass $class): string
    {
        $method = $class->hasMethod('__invoke') ? '__invoke' : ($class->hasMethod('handle') ? 'handle' : null);
        if ($method === null) {
            throw new MediatorException("{$class->getName()} needs an __invoke() or handle() method.");
        }

        $type = ($class->getMethod($method)->getParameters()[0] ?? null)?->getType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            throw new MediatorException("Cannot infer the message of {$class->getName()}::{$method}(): type its first parameter or add #[Handles].");
        }

        /** @var class-string */
        return $type->getName();
    }
}
