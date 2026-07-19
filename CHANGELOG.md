# Changelog

All notable changes to `elriseio/application-layer-bundle` are documented in
this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-19

### Added

- Repository `LICENSE` file (MIT) at the repo root, with the
  contributor copyright line `Copyright (c) 2026 Elrise.IO Alexk
  <alexk@elrise.io>` (AR-015). Packagist and most open-source
  tooling require this file by name (`LICENSE`, `LICENSE.md`,
  or `LICENSE.txt`); it ships verbatim alongside `composer.json`'s
  `license: MIT` declaration.
- Packagist readiness metadata on `composer.json`: `keywords`
  (`symfony`, `symfony-bundle`, `cqrs`, `ddd`, `application-layer`,
  `dto`, `messenger`) for faceted search (AR-010).
- Packagist UI metadata on `composer.json`: `homepage` (GitHub repo)
  and a `support` block with `issues`, `source`, and `docs` links
  (AR-014).
- `composer.json` carries `version: 1.0.0` removed in favour of git
  tags, plus `extra.branch-alias.dev-main = 1.x-dev` so
  `composer require elriseio/application-layer-bundle:dev-main`
  resolves before the first tag is published (AR-014).
- `composer.json` `scripts` block now exposes `cs:check`
  (`php-cs-fixer fix --dry-run --diff`), `test` (phpunit), and
  `check` (`[@cs:check, @test]`) alongside the existing `cs:fix`
  (AR-014).
- CQRS-shaped contracts: `CommandHandlerInterface` (mutating state)
  and `QueryHandlerInterface` (read-only), each registered through
  its own tagged service locator (`app_layer.command_handler` and
  `app_layer.query_handler`).
- `DataProcessorInterface` for DTO-less entry points (projections,
  lookup tables, computed reads), registered via the
  `app_layer.data_processor` tag.
- `DtoRequestHandler` with two explicit entry points:
  `dispatchCommand()` (runs sanitize → convert → resolve → invoke →
  optional queue dispatch) and `dispatchQuery()` (runs convert →
  resolve → invoke; sanitization is skipped because read-side input
  is not mutated).
- `DefaultRequestToDtoConverter` that extracts the payload from a
  Symfony `Request` (JSON body, or merged query + form) and hands
  it to a `DtoDeserializerInterface`.
- `SymfonyDtoDeserializer` with a built-in `selfDenormalizer` that
  handles two DTO shapes without external dependencies:
  - constructor DTOs (including readonly promoted properties) —
    delegates to `Symfony\Component\Serializer\Normalizer\ObjectNormalizer`;
  - property-only DTOs — instantiates via
    `ReflectionClass::newInstanceWithoutConstructor()` and writes
    fields via reflection.
- `MessengerQueueDispatcher` (auto-wired when `symfony/messenger`
  is installed) and `NullQueueDispatcher` (fallback when it is
  not). Both implement `DtoQueueDispatcherInterface` and are bound
  conditionally in `AppLayerProcessorExtension`.
- `RequestException` with a structured `details` array for
  diagnostic context (denormalization failures, handler-resolution
  failures, type mismatches).
- `RequestSanitizerInterface` (optional, commands only) with a
  default no-op `RequestSanitizer` implementation.
- Autoconfigure-tagged contract interfaces so consumer handlers and
  processors require no service declarations.

### Changed

- The single pre-1.0 `RequestHandlerInterface` is split into
  `CommandHandlerInterface` and `QueryHandlerInterface`. Each
  carries `#[AutoconfigureTag(...)]` and is resolved through its
  own `#[TaggedLocator(...)]` on `DtoRequestHandler`.
- The trait-map dispatch (`RequestHandlerTraitMapProviderInterface`
  and the default `DefaultRequestHandlerTraitMapProvider`) is
  removed. The old `RequestHandlerInterface` is gone.
- The `DataProcessorPass` compiler pass is removed in favour of the
  `#[TaggedLocator('app_layer.data_processor')]` attribute on
  `DataProcessor::__construct()`.
- `DtoRequestHandler::handle()` is replaced by
  `DtoRequestHandler::dispatchCommand()` and
  `DtoRequestHandler::dispatchQuery()`. The two methods are
  explicit about which steps of the pipeline they run.
- The property-only DTO deserialization path no longer calls
  `ReflectionProperty::setAccessible(true)`. PHP 8.1+ semantics
  allow direct assignment to typed properties regardless of
  visibility, so the deprecation warning is gone.
- The README is rewritten in English with a CQRS-shaped pipeline
  diagram and CQRS-flavoured examples (`CreateOrderHandler`,
  `ListOrdersHandler`).

### Removed

- `RequestHandlerInterface` and the trait-map dispatch layer
  (`RequestHandlerTraitMapProviderInterface`,
  `DefaultRequestHandlerTraitMapProvider`).
- The `DataProcessorPass` compiler pass.
- `ReflectionProperty::setAccessible(true)` from the property-only
  DTO path.

[1.0.0]: https://github.com/elriseio/application-layer-bundle/releases/tag/v1.0.0
