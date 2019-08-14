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
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\DefaultServicesProvider;
use Slim\Handlers\ErrorHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Middleware\ErrorMiddleware;
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
            InvocationStrategyInterface::class => [ self::class, 'getInvocationStrategy' ],
            ErrorHandler::class => [ self::class, 'getErrorHandler' ],
            ErrorHandlerInterface::class => [ self::class, 'getDefaultErrorHandler' ],
            ErrorMiddleware::class => [ self::class, 'getErrorMiddleware' ],
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

    public static function getInvocationStrategy(): InvocationStrategyInterface
    {
        return new RequestResponse;
    }

    public static function getDefaultErrorHandler(ContainerInterface $container): ErrorHandlerInterface
    {
        return $container->get(ErrorHandler::class);
    }

    public static function getErrorHandler(ContainerInterface $container): ErrorHandler
    {
        return new ErrorHandler(
            $container->get(CallableResolverInterface::class),
            $container->get(ResponseFactoryInterface::class)
        );
    }

    public static function getErrorMiddleware(ContainerInterface $c): ErrorMiddleware
    {
        $callableResolver = $c->get(CallableResolverInterface::class);
        $responseFactory = $c->get(ResponseFactoryInterface::class);
        $displayErrorDetails = $c->has('slim.display_error_details') ? $c->get('slim.display_error_details') : false;
        $logErrors = $c->has('slim.log_errors') ? $c->get('slim.log_errors') : false;
        $logErrorDetails = $c->has('slim.log_error_details') ? $c->get('slim.log_error_details') : false;

        $errorMiddleware = new ErrorMiddleware(
            $callableResolver,
            $responseFactory,
            $displayErrorDetails,
            $logErrors,
            $logErrorDetails
        );
        $errorMiddleware->setDefaultErrorHandler(ErrorHandlerInterface::class);

        return $errorMiddleware;
    }

    public static function getResponseFactory(ContainerInterface $container): ResponseFactoryInterface
    {
        return AppFactory::determineResponseFactory();
    }

    public static function getRouteCollector(ContainerInterface $container): RouteCollectorInterface
    {
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        $callableResolver = $container->get(CallableResolverInterface::class);
        $invocationStrategy = $container->get(InvocationStrategyInterface::class);
        $cacheFile = $container->has('slim.route_cache_file') ? $container->get('slim.route_cache_file') : null;

        return new RouteCollector(
            $responseFactory,
            $callableResolver,
            $container,
            $invocationStrategy,
            null,
            $cacheFile
        );
    }

    public static function getRouteResolver(ContainerInterface $container): RouteResolverInterface
    {
        return new RouteResolver(
            $container->get(RouteCollectorInterface::class),
            $container->get(DispatcherInterface::class)
        );
    }
}
