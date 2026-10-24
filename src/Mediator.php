<?php

declare(strict_types=1);

namespace Zana\Mediator;

use Psr\Container\ContainerInterface;

/**
 * Resolves the handler of each message from the container and calls it
 * through the behaviours, the first configured being the outermost.
 */
final readonly class Mediator implements MediatorInterface
{
    /**
     * @param list<PipelineBehavior|class-string<PipelineBehavior>> $behaviors
     */
    public function __construct(
        private ContainerInterface $container,
        private HandlerMap $handlers,
        private array $behaviors = [],
    ) {
    }

    public function send(object $message): mixed
    {
        $handlerClass = $this->handlers->handlerFor($message) ?? throw HandlerNotFoundException::for($message);

        $next = function (object $message) use ($handlerClass): mixed {
            $handler = $this->container->get($handlerClass);
            if (!is_object($handler)) {
                throw new MediatorException("The container returned no object for {$handlerClass}.");
            }

            return match (true) {
                is_callable($handler) => $handler($message),
                method_exists($handler, 'handle') => $handler->handle($message),
                default => throw new MediatorException("{$handlerClass} needs an __invoke() or handle() method."),
            };
        };

        foreach (array_reverse($this->behaviors) as $behavior) {
            $inner = $next;
            $next = fn (object $message): mixed => $this->behavior($behavior)->handle($message, $inner);
        }

        return $next($message);
    }

    /**
     * @param PipelineBehavior|class-string<PipelineBehavior> $behavior
     */
    private function behavior(PipelineBehavior|string $behavior): PipelineBehavior
    {
        if ($behavior instanceof PipelineBehavior) {
            return $behavior;
        }

        $instance = $this->container->get($behavior);

        return $instance instanceof PipelineBehavior
            ? $instance
            : throw new MediatorException("{$behavior} does not implement " . PipelineBehavior::class . '.');
    }
}
