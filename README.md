# Container Interop Service Provider for Slim

Provides
[container-interop/service-provider](https://github.com/container-interop/service-provider)
support for Slim.

## Installation

```sh
$ composer require bnf/slim-interop-service-provider:^4.0
```

## Usage

Add `Bnf\SlimInterop\ServiceProvider` to the list of service providers to register the default Slim services.

Specify it as **first** service provider to be able to overwrite or extend default services.

```php
new Container([
    new \Bnf\SlimInterop\ServiceProvider,
    new YouServiceProvider,
]);
```

## Example

Example usage with a `container-interop/service-provider` compatible container `bnf/di`.

```sh
$ composer require bnf/slim-interop-service-provider:^4.0 bnf/di:~0.1.0 slim/slim:^4.0 slim/psr7:~0.4.0
```

```php
<?php
declare(strict_types = 1);
require 'vendor/autoload.php';

use Bnf\Di\Container;
use Bnf\SlimInterop\ServiceProvider as SlimServiceProvider;
use Psr\Container\ContainerInterface;
use Interop\Container\ServiceProviderInterface;
use Slim\App;

class Index
{
    public function __invoke($request, $response) {
        $response->getBody()->write('Hello World.');
        return $response;
    }
}

$container = new Container([
    // Register default slim services
    new SlimServiceProvider,

    // Register own services and configuration
    new class implements ServiceProviderInterface {
        public function getFactories(): array
        {
            return [
                Index::class => function (ContainerInterface $container): Index {
                    return new Index;
                }
            ];
        }
        public function getExtensions(): array
        {
            return [
                // configure app route and middlewares as extension to the App class
                App::class => function (ContainerInterface $container, App $app): App {
                    $app->addErrorMiddleware(true, true, true);

                    $app->get('/', Index::class);

                    return $app;
                },
            ];
        }
    }
]);

$app = $container->get(App::class);
$app->run();
```
