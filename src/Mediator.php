<?php

declare(strict_types=1);

namespace Zana\Mediator;

use Zana\Container\Contracts\ContainerInterface;
use Zana\Enterprise\Application\Command;
use Zana\Enterprise\Application\Query;
use Zana\Enterprise\Application\Contracts\CommandHandlerInterface;
use Zana\Enterprise\Application\Contracts\QueryHandlerInterface;
use Zana\Mediator\Contracts\MediatorInterface;

/**
 * CQRS Mediator implementation.
 * 
 * Dispatches commands and queries to their handlers using the service container.
 * Handlers are resolved automatically based on naming convention:
 * - Command `CalculateBmiCommand` → handler `CalculateBmiHandler`
 * - Query `GetHealthCategoryQuery` → handler `GetHealthCategoryHandler`
 */
class Mediator implements MediatorInterface
{
    private ContainerInterface $container;

    /** @var array<string, string> Custom command handler mappings */
    private array $commandHandlers = [];

    /** @var array<string, string> Custom query handler mappings */
    private array $queryHandlers = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register a custom command handler mapping.
     *
     * @param string $commandClass
     * @param string $handlerClass
     */
    public function registerCommandHandler(string $commandClass, string $handlerClass): void
    {
        $this->commandHandlers[$commandClass] = $handlerClass;
    }

    /**
     * Register a custom query handler mapping.
     *
     * @param string $queryClass
     * @param string $handlerClass
     */
    public function registerQueryHandler(string $queryClass, string $handlerClass): void
    {
        $this->queryHandlers[$queryClass] = $handlerClass;
    }

    /**
     * Dispatch a command to its handler.
     *
     * @param Command $command
     * @return mixed
     */
    public function dispatch(Command $command): mixed
    {
        $commandClass = get_class($command);
        $handlerClass = $this->resolveCommandHandler($commandClass);

        $handler = $this->container->get($handlerClass);

        if (!$handler instanceof CommandHandlerInterface) {
            throw new \RuntimeException(
                "Handler '{$handlerClass}' must implement CommandHandlerInterface."
            );
        }

        return $handler->handle($command);
    }

    /**
     * Dispatch a query to its handler.
     *
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query): mixed
    {
        $queryClass = get_class($query);
        $handlerClass = $this->resolveQueryHandler($queryClass);

        $handler = $this->container->get($handlerClass);

        if (!$handler instanceof QueryHandlerInterface) {
            throw new \RuntimeException(
                "Handler '{$handlerClass}' must implement QueryHandlerInterface."
            );
        }

        return $handler->handle($query);
    }

    /**
     * Resolve the handler class for a command.
     * 
     * Convention: App\Modules\BMI\Application\Commands\CalculateBmiCommand
     *           → App\Modules\BMI\Application\Handlers\CalculateBmiHandler
     *
     * @param string $commandClass
     * @return string
     */
    private function resolveCommandHandler(string $commandClass): string
    {
        // Check custom mappings first
        if (isset($this->commandHandlers[$commandClass])) {
            return $this->commandHandlers[$commandClass];
        }

        // Convention-based resolution
        $handlerClass = preg_replace('/Commands\\\\(.*)Command$/', 'Handlers\\\$1Handler', $commandClass)
            ?? str_replace('Command', 'Handler', $commandClass);

        if (!class_exists($handlerClass)) {
            throw new \RuntimeException(
                "Command handler not found for '{$commandClass}'. " .
                "Expected: {$handlerClass}"
            );
        }

        return $handlerClass;
    }

    /**
     * Resolve the handler class for a query.
     * 
     * Convention: App\Modules\BMI\Application\Queries\GetHealthCategoryQuery
     *           → App\Modules\BMI\Application\Handlers\GetHealthCategoryHandler
     *
     * @param string $queryClass
     * @return string
     */
    private function resolveQueryHandler(string $queryClass): string
    {
        // Check custom mappings first
        if (isset($this->queryHandlers[$queryClass])) {
            return $this->queryHandlers[$queryClass];
        }

        // Convention-based resolution
        $handlerClass = preg_replace('/Queries\\\\(.*)Query$/', 'Handlers\\\$1Handler', $queryClass)
            ?? str_replace('Query', 'Handler', $queryClass);

        if (!class_exists($handlerClass)) {
            throw new \RuntimeException(
                "Query handler not found for '{$queryClass}'. " .
                "Expected: {$handlerClass}"
            );
        }

        return $handlerClass;
    }
}

