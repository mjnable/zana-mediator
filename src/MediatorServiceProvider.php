<?php

declare(strict_types=1);

namespace Zana\Mediator;

use Zana\Configuration\ConfigurationInterface;
use Zana\Container\Contracts\ServiceCollectionInterface;
use Zana\Container\Contracts\ServiceResolverInterface;
use Zana\Core\ServiceProvider;

/**
 * Registers the handler map (singleton) and the mediator (scoped, so handlers
 * resolve from the current request or command scope). Behaviours come from
 * mediator.behaviors; modules map handlers in boot().
 */
final class MediatorServiceProvider extends ServiceProvider
{
    public function register(ServiceCollectionInterface $services): void
    {
        $services
            ->singleton(HandlerMap::class)
            ->scoped(MediatorInterface::class, static function (ServiceResolverInterface $scope): MediatorInterface {
                /** @var list<class-string<PipelineBehavior>> $behaviors */
                $behaviors = $scope->get(ConfigurationInterface::class)->array('mediator.behaviors', []);

                return new Mediator($scope, $scope->get(HandlerMap::class), $behaviors);
            })->uses(ConfigurationInterface::class, HandlerMap::class)
            ->alias(Mediator::class, MediatorInterface::class);
    }

    public function origin(): string
    {
        return 'package:mj-zana/mediator';
    }
}
