# AppLayerBundle

[![CI](https://github.com/elriseio/application-layer-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/elriseio/application-layer-bundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/elriseio/application-layer-bundle/v)](https://packagist.org/packages/elriseio/application-layer-bundle)
[![Total Downloads](https://poser.pugx.org/elriseio/application-layer-bundle/downloads)](https://packagist.org/packages/elriseio/application-layer-bundle)
[![License](https://poser.pugx.org/elriseio/application-layer-bundle/license)](https://github.com/elriseio/application-layer-bundle/blob/main/LICENSE)

Symfony bundle that implements the application boundary of a CQRS-shaped
DDD system. It carries the HTTP request through sanitization → DTO
denormalization → command or query handler invocation → optional queue
dispatch, with first-class interfaces for `CommandHandlerInterface` and
`QueryHandlerInterface`.

The bundle is transport-agnostic: it works in plain Symfony controllers,
under API Platform state providers and processors, in Messenger
handlers, or in console commands. It depends only on
`symfony/serializer` and the standard Symfony service container, with
`symfony/messenger` as an optional integration for async commands.

## Why it exists

A typical Symfony HTTP handler conflates three concerns:

- request parsing and validation (transport),
- mapping the validated payload into a use-case input (application layer),
- orchestrating the use-case against the domain model (application/domain).

`AppLayerBundle` claims the middle slice. The boundary between
transport and use-case is an immutable DTO. The use-case itself is
expressed as a `CommandHandler` (mutates state, returns a result) or a
`QueryHandler` (returns read-side data). The handler can use API
Platform, Messenger, custom repositories, or anything else — the
bundle imposes no constraint beyond the contract.

This shape keeps the use-case unit-testable in isolation, makes the
intent of every endpoint explicit (command or query), and lets the
transport layer (controllers, API Platform, RPC, CLI) stay a thin
adapter.

## Key features

- **CQRS-shaped contracts**: distinct `CommandHandlerInterface` and
  `QueryHandlerInterface`, registered through separate tagged locators.
- **Immutable DTO denormalization** through `symfony/serializer`, with
  built-in support for readonly constructor-promoted DTOs and
  property-only DTOs (no `setAccessible` since PHP 8.5).
- **Optional request sanitization** for command payloads. Queries skip
  sanitization by design — read-side input is not mutated.
- **Synchronous and asynchronous command handling** via
  `symfony/messenger`. The `MessengerQueueDispatcher` is wired
  automatically when the package is installed; otherwise a
  `NullQueueDispatcher` is used.
- **Pluggable processor pipeline** (`DataProcessorInterface`) for
  endpoints that don't carry a DTO (lookup tables, projections, etc.).
- **Structured error handling** through `RequestException`, which
  wraps every denormalization, locator, and handler-resolution
  failure with diagnostic context.

## Architecture

```
HTTP Request
    │
    ▼
DtoRequestHandler
    │
    ├── sanitize  (RequestSanitizerInterface, commands only)
    ├── convert   (RequestToDtoConverterInterface → SymfonyDtoDeserializer)
    ├── resolve   (commandLocator | queryLocator)
    ├── invoke    (CommandHandlerInterface.handle | QueryHandlerInterface.handle)
    └── dispatch  (DtoQueueDispatcherInterface, commands only, async opt-in)
```

### Contracts

- `CommandHandlerInterface` — tagged `app_layer.command_handler`. Mutates
  state and returns a command result (id, presenter, view DTO).
- `QueryHandlerInterface` — tagged `app_layer.query_handler`. Returns
  read-side data without side effects.
- `DataProcessorInterface` — tagged `app_layer.data_processor`. Used
  for endpoints without a DTO.
- `DtoDeserializerInterface` — abstraction over the underlying
  denormalizer. Default: `SymfonyDtoDeserializer`.
- `RequestToDtoConverterInterface` — extracts the payload from a
  Symfony `Request` and turns it into a DTO.
- `RequestSanitizerInterface` — optional pre-DTO cleanup for commands.

### Components

- `DtoRequestHandler` — orchestrates the pipeline above. Two entry
  points: `dispatchCommand()` and `dispatchQuery()`.
- `DefaultRequestToDtoConverter` — JSON body or query/form merger, then
  DTO denormalization.
- `SymfonyDtoDeserializer` — routes DTOs with constructors to
  `ObjectNormalizer`; handles property-only DTOs via direct reflection
  (no `setAccessible`).
- `DataProcessor` — tagged locator for processor-only endpoints.

### Dispatchers

- `DtoQueueDispatcherInterface` — the abstract dispatch boundary.
- `MessengerQueueDispatcher` — implemented when `symfony/messenger` is
  installed.
- `NullQueueDispatcher` — fallback when no transport is installed.

## Installation

```bash
composer require elriseio/application-layer-bundle
```

## Requirements

- PHP 8.3 or higher with the `ctype`, `curl`, and `json` extensions
  enabled (all three are bundled by default in standard PHP
  distributions; they are listed in `composer.json` `require` for
  runtime-declaration clarity).
- Symfony 7.2 or higher

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    Elrise\Bundle\AppLayerBundle\AppLayerBundle::class => ['all' => true],
];
```

## Usage

### 1. Define an immutable DTO

```php
final readonly class CreateOrderCommand
{
    public function __construct(
        public string $customerId,
        public array $items,
    ) {
    }
}

final readonly class ListOrdersQuery
{
    public function __construct(
        public string $customerId,
        public int $limit = 20,
    ) {
    }
}
```

### 2. Implement a command handler

```php
use Elrise\Bundle\AppLayerBundle\Contract\CommandHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

final class CreateOrderHandler implements CommandHandlerInterface
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(Request $request, object $command): mixed
    {
        \assert($command instanceof CreateOrderCommand);

        $order = $this->orders->create($command);

        return ['id' => $order->id()];
    }
}
```

### 3. Implement a query handler

```php
use Elrise\Bundle\AppLayerBundle\Contract\QueryHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ListOrdersHandler implements QueryHandlerInterface
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(Request $request, object $query): mixed
    {
        \assert($query instanceof ListOrdersQuery);

        return $this->orders->listFor($query->customerId, $query->limit);
    }
}
```

### 4. Dispatch from a controller

```php
final class OrderController
{
    public function __construct(private DtoRequestHandler $handler) {}

    #[Route('/orders', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $result = $this->handler->dispatchCommand(
            request: $request,
            commandFqcn: CreateOrderCommand::class,
            handlerFqcn: CreateOrderHandler::class,
        );

        return new JsonResponse($result, 201);
    }

    #[Route('/orders', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $items = $this->handler->dispatchQuery(
            request: $request,
            queryFqcn: ListOrdersQuery::class,
            handlerFqcn: ListOrdersHandler::class,
        );

        return new JsonResponse(['items' => $items]);
    }
}
```

To dispatch asynchronously, pass `dispatchToQueue: true` to
`dispatchCommand`. The handler still runs synchronously, and the
already-mutating command is also handed to the configured queue
dispatcher (Messenger, by default) for downstream consumers.

### 5. Use the processor pipeline (DTO-less endpoints)

```php
use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

final class OrderSummaryProcessor implements DataProcessorInterface
{
    public function __construct(private SummaryService $summary) {}

    public function process(Request $request): mixed
    {
        return $this->summary->build();
    }
}

// In a controller:
$result = $this->dataProcessor->process($request, OrderSummaryProcessor::class);
```

## API Platform integration

API Platform's `Processor` and `Provider` interfaces map naturally onto
`DtoRequestHandler`. The recommended pattern is to keep the API
Platform entity/state purely as a transport adapter and delegate to
the application layer for the actual use-case.

### State Provider for read endpoints

```php
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use Symfony\Component\HttpFoundation\Request;

final class OrderListProvider implements ProviderInterface
{
    public function __construct(private DtoRequestHandler $handler) {}

    public function provide(Get $operation, array $uriVariables = [], array $context = []): iterable
    {
        $result = $this->handler->dispatchQuery(
            request: Request::createFromGlobals(),
            queryFqcn: ListOrdersQuery::class,
            handlerFqcn: ListOrdersHandler::class,
        );

        return $result;
    }
}
```

### Processor for write endpoints

```php
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use Symfony\Component\HttpFoundation\Request;

final class CreateOrderProcessor implements ProcessorInterface
{
    public function __construct(private DtoRequestHandler $handler) {}

    public function process(mixed $data, Post $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $this->handler->dispatchCommand(
            request: Request::createFromGlobals(),
            commandFqcn: CreateOrderCommand::class,
            handlerFqcn: CreateOrderHandler::class,
        );
    }
}
```

The boundary stays explicit: API Platform owns OpenAPI, content
negotiation, rate limits, and the response shape. The application
layer owns the use-case. DDD aggregates, repositories, and domain
services live one layer below, called only from the command/query
handlers.

## Testing

The bundle ships with PHPUnit coverage for the pipeline. Run:

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Development

The bundle ships with a project-local pre-commit hook that runs
`composer check` (`cs:check` + `test`) so style drift and test
breakage are caught locally before push. The hook is wired through
`core.hooksPath`, so it only takes effect inside this checkout.

Install the hook once after cloning:

```bash
./scripts/install-hooks.sh
```

This sets `core.hooksPath` to `./.githooks`. The hook then runs
automatically before every commit; bypass it with `git commit --no-verify`
when a commit legitimately needs to land without a re-run (for example,
a `composer.lock` rotation triggered by a maintainer-only action).

`composer install` does not auto-install the hook on purpose: CI must
not be polluted by `git config` calls, and the operator may prefer
their own tooling (Lefthook, Husky) over the bundled bash hook.

## License

MIT. See [LICENSE](LICENSE).
