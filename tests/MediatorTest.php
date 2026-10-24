<?php

declare(strict_types=1);

namespace Zana\Mediator\Tests;

use PHPUnit\Framework\TestCase;
use Zana\Container\Contracts\ServiceCollectionInterface;
use Zana\Container\ServiceCollection;
use Zana\Core\Application;
use Zana\Mediator\HandlerMap;
use Zana\Mediator\HandlerNotFoundException;
use Zana\Mediator\Mediator;
use Zana\Mediator\MediatorException;
use Zana\Mediator\MediatorInterface;
use Zana\Mediator\MediatorServiceProvider;

final class MediatorTest extends TestCase
{
    public function testMessagesReachTheirHandler(): void
    {
        $mediator = $this->mediator((new HandlerMap())->register(CalculateBmiHandler::class));

        $this->assertSame(24.7, $mediator->send(new CalculateBmi(80, 1.8)));
    }

    public function testAttributesMapSeveralMessagesToHandle(): void
    {
        $mediator = $this->mediator((new HandlerMap())->register(MultiHandler::class));

        $this->assertSame(Archive::class, $mediator->send(new Archive()));
        $this->assertSame(Ping::class, $mediator->send(new Ping()));
    }

    public function testLookupFallsBackToParentsThenInterfaces(): void
    {
        $map = (new HandlerMap())->register(CalculateBmiHandler::class)->map(AuditableMessage::class, AuditHandler::class);
        $mediator = $this->mediator($map);

        $this->assertSame(10.0, $mediator->send(new CalculateChildBmi(10, 1)), 'the parent class wins over the interface');
        $this->assertSame('audited', $mediator->send(new Archive()));
    }

    public function testUnknownMessagesAreReported(): void
    {
        $this->expectException(HandlerNotFoundException::class);
        $this->expectExceptionMessage('No handler is mapped for ' . Ping::class);

        $this->mediator(new HandlerMap())->send(new Ping());
    }

    public function testInvalidMappingsAreRejected(): void
    {
        $map = (new HandlerMap())->register(CalculateBmiHandler::class)->register(CalculateBmiHandler::class);
        $this->assertSame([CalculateBmi::class => CalculateBmiHandler::class], $map->all(), 'mapping twice to the same handler is harmless');

        foreach ([
            [static fn () => $map->map(CalculateBmi::class, AuditHandler::class), 'is already handled by'],
            [static fn () => (new HandlerMap())->register(UntypedHandler::class), 'Cannot infer the message'],
            [static fn () => (new HandlerMap())->register(NoMethodHandler::class), 'needs an __invoke() or handle() method'],
        ] as [$call, $message]) {
            try {
                $call();
                $this->fail("Expected: {$message}");
            } catch (MediatorException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testBehaviorsWrapTheHandlerOutermostFirst(): void
    {
        $command = new CalculateBmi(80, 1.8);
        $mediator = $this->mediator((new HandlerMap())->register(CalculateBmiHandler::class), [new TraceBehavior('outer'), TraceBehavior::class]);

        $mediator->send($command);

        $this->assertSame(['outer:in', 'trace:in', 'handler', 'trace:out', 'outer:out'], $command->trail);
    }

    public function testBehaviorsCanShortCircuit(): void
    {
        $command = new CalculateBmi(80, 1.8);
        $mediator = $this->mediator((new HandlerMap())->register(CalculateBmiHandler::class), [new ShortCircuitBehavior()]);

        $this->assertSame('cached', $mediator->send($command));
        $this->assertSame([], $command->trail);
    }

    public function testInvalidBehaviorsAreRejected(): void
    {
        $container = (new ServiceCollection())->singleton(CalculateBmiHandler::class)->singleton('bad', static fn (): \stdClass => new \stdClass())->build();
        /** @phpstan-ignore argument.type (an invalid behaviour is under test) */
        $mediator = new Mediator($container, (new HandlerMap())->register(CalculateBmiHandler::class), ['bad']);

        $this->expectException(MediatorException::class);
        $mediator->send(new CalculateBmi(1, 1));
    }

    public function testTheProviderRegistersAScopedMediator(): void
    {
        $base = sys_get_temp_dir() . '/zana-mediator-' . bin2hex(random_bytes(4));
        mkdir("{$base}/config", 0777, true);
        file_put_contents("{$base}/config/mediator.php", '<?php return ["behaviors" => [' . var_export(TraceBehavior::class, true) . ']];');

        $container = Application::configure($base)
            ->withProviders(MediatorServiceProvider::class)
            ->withServices(static fn (ServiceCollectionInterface $s) => $s->scoped(ScopedHandler::class)->transient(CalculateBmiHandler::class)->transient(TraceBehavior::class))
            ->build()
            ->container();
        $container->get(HandlerMap::class)->register(ScopedHandler::class, CalculateBmiHandler::class);

        $scope = $container->createScope(\Zana\Container\ScopeMetadata::cli('bmi'));
        $mediator = $scope->get(MediatorInterface::class);
        $command = new CalculateBmi(80, 1.8);
        $mediator->send($command);

        $this->assertSame('cli', $mediator->send(new Ping()), 'handlers resolve from the current scope');
        $this->assertSame(['trace:in', 'handler', 'trace:out'], $command->trail, 'configured behaviours apply');
        $this->assertSame($mediator, $scope->get(Mediator::class));

        unlink("{$base}/config/mediator.php");
        rmdir("{$base}/config");
        rmdir($base);
    }

    /**
     * @param list<\Zana\Mediator\PipelineBehavior|class-string<\Zana\Mediator\PipelineBehavior>> $behaviors
     */
    private function mediator(HandlerMap $map, array $behaviors = []): Mediator
    {
        $container = (new ServiceCollection())
            ->transient(CalculateBmiHandler::class)
            ->transient(MultiHandler::class)
            ->transient(AuditHandler::class)
            ->transient(TraceBehavior::class)
            ->build();

        return new Mediator($container, $map, $behaviors);
    }
}
