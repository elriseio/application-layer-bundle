# AppLayerBundle

## Description

AppLayerBundle is a Symfony bundle designed to implement the application’s base layer (Application Layer) based on CQRS (Command Query Responsibility Segregation) principles and DTO (Data Transfer Objects). The bundle provides infrastructure for handling HTTP requests, sanitizing them, converting them into DTOs, processing them synchronously or asynchronously via processors, and dispatching them to queues.

The core idea of the bundle is to separate request handling logic from business logic, ensuring a clean architecture where commands (state changes) and queries (data reads) are handled independently. This approach enables scalable and testable applications.

## Key Features

- **DTO-based request handling**: Conversion of HTTP requests into DTO objects for further processing.
- **Request sanitization**: Optional cleaning of incoming data from unwanted content.
- **Synchronous and asynchronous processing**: Support for immediate handling as well as queue dispatching using Symfony Messenger.
- **Data processors**: A mechanism for processing data via specialized processors.
- **DTO deserialization**: Use of Symfony Serializer to transform data into DTOs.
- **Flexible configuration**: Integration with the Symfony Dependency Injection Container for automatic service registration.

## Architecture

The bundle consists of the following key components:

### Contracts
- `RequestHandlerInterface`: Interface for request handlers that accept DTOs.
- `DataProcessorInterface`: Interface for data processors.
- `DtoDeserializerInterface`: Interface for DTO deserialization.
- `RequestToDtoConverterInterface`: Interface for converting requests into DTOs.
- `RequestSanitizerInterface`: Interface for request sanitization.
- `RequestHandlerTraitMapProviderInterface`: Interface for providing handler trait mappings.

### Handlers
- `DtoRequestHandler`: The main class for request handling. It sanitizes the request, converts it into a DTO, and either processes it synchronously or dispatches it to a queue.
- `DefaultRequestToDtoConverter`: Default implementation of the request-to-DTO converter.
- `RequestSanitizer`: Class responsible for request sanitization.

### Processors
- `DataProcessor`: A class for processing data through registered processors.

### Dispatchers
- `DtoQueueDispatcherInterface`: Interface for dispatching DTOs to a queue.
- `MessengerQueueDispatcher`: Implementation based on Symfony Messenger.
- `NullQueueDispatcher`: Stub implementation for cases where dispatching is not required.

### Serialization
- `SymfonyDtoDeserializer`: DTO deserializer implementation based on Symfony Serializer.

### Exceptions
- `RequestException`: Exception for request processing errors.

### Dependency Injection
- `AppLayerProcessorExtension`: Extension for configuring the dependency injection container.

## Installation

Add the bundle to your project via Composer:

```bash
composer require elrise/application-layer
````

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Elrise\Bundle\AppLayerBundle\AppLayerBundle::class => ['all' => true],
];
```

## Usage

### 1. Creating a DTO

Create a DTO class, for example:

```php
<?php

namespace App\Dto;

class CreateUserDto
{
    public string $name;
    public string $email;
}
```

### 2. Creating a Request Handler

Implement `RequestHandlerInterface`:

```php
<?php

namespace App\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateUserHandler implements RequestHandlerInterface
{
    public function handle(Request $request, object $dto): object
    {
        // Processing logic
        // $dto instanceof CreateUserDto
        // Returns a result (e.g., a new user object)
    }
}
```

### 3. Using DtoRequestHandler

In a controller or service:

```php
<?php

use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use Symfony\Component\HttpFoundation\Request;

class UserController
{
    public function __construct(
        private DtoRequestHandler $requestHandler,
    ) {}

    public function createUser(Request $request): Response
    {
        $result = $this->requestHandler->handle(
            $request,
            CreateUserDto::class,
            CreateUserHandler::class,
            dispatchToQueue: false // or true for asynchronous processing
        );

        // Handle the result
    }
}
```

### 4. Using DataProcessor

For data processing without DTOs:

```php
<?php

use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class GetUserProcessor implements DataProcessorInterface
{
    public function process(Request $request): mixed
    {
        // Data processing logic
    }
}
```

In a service:

```php
use Elrise\Bundle\AppLayerBundle\Processor\DataProcessor;

$result = $this->dataProcessor->process($request, GetUserProcessor::class);
```

## Configuration

The bundle automatically registers services with the corresponding tags:

* `app_layer.dto_request_handler` for request handlers
* `app_layer.data_processor` for data processors

Asynchronous processing requires the `symfony/messenger` package.

## Testing

The bundle includes a test suite. Run it using PHPUnit:

```bash
./vendor/bin/phpunit
```

## License

This bundle is distributed under the MIT license.

## Authors

* Alex ([alexk@elrise.ru](mailto:alexk@elrise.ru))

