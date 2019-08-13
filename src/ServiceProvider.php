<?php
declare(strict_types = 1);
namespace Bnf\SlimInterop;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\CallableResolver;
use Slim\Factory\AppFactory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\DefaultServicesProvider;
use Slim\Routing\Dispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;

class ServiceProvider implements ServiceProviderInterface
{

    public function getFactories(): array
    {
        return [
            App::class => [ self::class, 'getApp' ],
            CallableResolverInterface::class => [ self::class, 'getCallableResolver' ],
            DispatcherInterface::class => [ self::class, 'getDispatcher' ],
            ResponseFactoryInterface::class => [ self::class, 'getResponseFactory' ],
            RouteCollectorInterface::class => [ self::class, 'getRouteCollector' ],
            RouteResolverInterface::class => [ self::class, 'getRouteResolver' ],
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }

    public static function getApp(ContainerInterface $container): App
    {
        return AppFactory::create(
            $container->get(ResponseFactoryInterface::class),
            $container,
            $container->get(CallableResolverInterface::class),
            $container->get(RouteCollectorInterface::class),
            $container->get(RouteResolverInterface::class)
        );
    }

    public static function getResponseFactory(ContainerInterface $container): ResponseFactoryInterface
    {
        return AppFactory::determineResponseFactory();
    }

    public static function getCallableResolver(ContainerInterface $container): CallableResolverInterface
    {
        return new CallableResolver($container);
    }

    public static function getDispatcher(ContainerInterface $container): DispatcherInterface
    {
        return new Dispatcher(
            $container->get(RouteCollectorInterface::class)
        );
    }

    public static function getRouteCollector(ContainerInterface $container): RouteCollectorInterface
    {
        $responseFactory = $container->has(ResponseFactoryInterface::class) ?
            $container->get(ResponseFactoryInterface::class) : AppFactory::determineResponseFactory();
        $callableResolver = $container->get(CallableResolverInterface::class);
        // @todo
        $invocationStrategy = null;
        $cacheFile = $container->has('slim.route_cache_file') ? $container->get('slim.route_cache_file') : null;

        return new RouteCollector($responseFactory, $callableResolver, $container, $invocationStrategy, $cacheFile);
    }

    public static function getRouteResolver(ContainerInterface $container): RouteResolverInterface
    {
        return new RouteResolver(
            $container->get(RouteCollectorInterface::class),
            $container->get(DispatcherInterface::class)
        );
    }
}
