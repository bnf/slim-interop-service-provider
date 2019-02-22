<?php
declare(strict_types = 1);
namespace Bnf\SlimInterop;

use ArrayObject;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\DefaultServicesProvider;

class ServiceProvider implements ServiceProviderInterface
{

    public function getFactories(): array
    {
        $fakeContainer = new ArrayObject([
            App::class => [ self::class, 'getApp' ],
        ]);

        $defaultServiceProvider = new DefaultServicesProvider;
        $defaultServiceProvider->register($fakeContainer);

        return $fakeContainer->getArrayCopy();
    }

    public function getExtensions(): array
    {
        return [
            'settings' => [ self::class, 'addDefaultSettings' ]
        ];
    }

    public static function getApp(ContainerInterface $container): App
    {
        return new App($container);
    }

    public static function addDefaultSettings(ContainerInterface $container, array $settings = null): array
    {
        $settings = $settings ?? [];

        $defaultSlimSettings = [
            'httpVersion' => '1.1',
            'responseChunkSize' => 4096,
            'outputBuffering' => 'append',
            'determineRouteBeforeAppMiddleware' => false,
            'displayErrorDetails' => false,
            'addContentLengthHeader' => true,
            'routerCacheFile' => false,
        ];

        return $settings + $defaultSlimSettings;
    }
}
