<?php

declare(strict_types=1);

namespace Zana\Mediator\Tests;

use PHPUnit\Framework\TestCase;
use Zana\Container\ServiceCollection;
use Zana\Mediator\Mediator;
use Zana\Enterprise\Application\Command;
use Zana\Enterprise\Application\Query;
use Zana\Enterprise\Application\Contracts\CommandHandlerInterface;
use Zana\Enterprise\Application\Contracts\QueryHandlerInterface;

class MediatorTest extends TestCase
{
    public function testDispatchUsesConventionToResolveHandler(): void
    {
        $services = new ServiceCollection();
        $services->transient(TestCommandHandler::class);
        $container = $services->build();

        $mediator = new Mediator($container);
        $result = $mediator->dispatch(new TestCommand('hello'));

        $this->assertEquals('handled:hello', $result);
    }

    public function testQueryUsesConventionToResolveHandler(): void
    {
        $services = new ServiceCollection();
        $services->transient(TestQueryHandler::class);
        $container = $services->build();

        $mediator = new Mediator($container);
        $result = $mediator->query(new TestQuery('world'));

        $this->assertEquals('result:world', $result);
    }

    public function testCustomCommandHandlerMapping(): void
    {
        $services = new ServiceCollection();
        $services->transient(CustomCommandHandler::class);
        $container = $services->build();

        $mediator = new Mediator($container);
        $mediator->registerCommandHandler(TestCommand::class, CustomCommandHandler::class);

        $result = $mediator->dispatch(new TestCommand('x'));
        $this->assertEquals('custom:x', $result);
    }

    public function testCustomQueryHandlerMapping(): void
    {
        $services = new ServiceCollection();
        $services->transient(CustomQueryHandler::class);
        $container = $services->build();

        $mediator = new Mediator($container);
        $mediator->registerQueryHandler(TestQuery::class, CustomQueryHandler::class);

        $result = $mediator->query(new TestQuery('y'));
        $this->assertEquals('custom-query:y', $result);
    }

    public function testDispatchThrowsWhenHandlerNotFound(): void
    {
        $services = new ServiceCollection();
        $container = $services->build();

        $mediator = new Mediator($container);

        $this->expectException(\RuntimeException::class);
        $mediator->dispatch(new TestCommand('z'));
    }

    public function testDispatchThrowsWhenHandlerIsNotCommandHandler(): void
    {
        $services = new ServiceCollection();
        $services->transient(NotACommandHandler::class);
        $container = $services->build();

        $mediator = new Mediator($container);
        $mediator->registerCommandHandler(TestCommand::class, NotACommandHandler::class);

        $this->expectException(\RuntimeException::class);
        $mediator->dispatch(new TestCommand('z'));
    }
}

class TestCommand extends Command
{
    public function __construct(private string $value)
    {
        parent::__construct();
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class TestCommandHandler implements CommandHandlerInterface
{
    public function handle(Command $command): mixed
    {
        return 'handled:' . $command->getValue();
    }
}

class CustomCommandHandler implements CommandHandlerInterface
{
    public function handle(Command $command): mixed
    {
        return 'custom:' . $command->getValue();
    }
}

class NotACommandHandler
{
}

class TestQuery extends Query
{
    public function __construct(private string $value)
    {
        parent::__construct();
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class TestQueryHandler implements QueryHandlerInterface
{
    public function handle(Query $query): mixed
    {
        return 'result:' . $query->getValue();
    }
}

class CustomQueryHandler implements QueryHandlerInterface
{
    public function handle(Query $query): mixed
    {
        return 'custom-query:' . $query->getValue();
    }
}

