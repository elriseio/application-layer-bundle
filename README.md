# AppLayerBundle

## Описание

AppLayerBundle — это Symfony бандл, предназначенный для реализации базового слоя приложения (Application Layer) на основе принципов CQRS (Command Query Responsibility Segregation) и работы с DTO (Data Transfer Objects). Бандл предоставляет инфраструктуру для обработки HTTP-запросов, их санитизации, преобразования в DTO, синхронной или асинхронной обработки через процессоры и диспетчеризацию в очереди.

Основная идея бандла — отделить логику обработки запросов от бизнес-логики, обеспечивая чистую архитектуру, где команды (изменения состояния) и запросы (чтение данных) обрабатываются отдельно. Это позволяет строить масштабируемые и тестируемые приложения.

## Основные возможности

- **Обработка запросов с DTO**: Преобразование HTTP-запросов в объекты DTO для дальнейшей обработки.
- **Санитизация запросов**: Опциональная очистка входящих данных от нежелательного контента.
- **Синхронная и асинхронная обработка**: Поддержка как немедленной обработки, так и диспетчеризации в очередь с использованием Symfony Messenger.
- **Процессоры данных**: Механизм для обработки данных через специализированные процессоры.
- **Десериализация DTO**: Использование Symfony Serializer для преобразования данных в DTO.
- **Гибкая конфигурация**: Интеграция с Symfony Dependency Injection Container для автоматической регистрации сервисов.

## Архитектура

Бандл состоит из следующих ключевых компонентов:

### Контракты (Contracts)
- `RequestHandlerInterface`: Интерфейс для обработчиков запросов, принимающих DTO.
- `DataProcessorInterface`: Интерфейс для процессоров данных.
- `DtoDeserializerInterface`: Интерфейс для десериализации в DTO.
- `RequestToDtoConverterInterface`: Интерфейс для конвертации запросов в DTO.
- `RequestSanitizerInterface`: Интерфейс для санитизации запросов.
- `RequestHandlerTraitMapProviderInterface`: Интерфейс для предоставления маппинга трейтов обработчиков.

### Обработчики (Handlers)
- `DtoRequestHandler`: Основной класс для обработки запросов. Санитизирует запрос, конвертирует в DTO и либо обрабатывает синхронно, либо диспетчеризует в очередь.
- `DefaultRequestToDtoConverter`: Стандартная реализация конвертера запросов в DTO.
- `RequestSanitizer`: Класс для санитизации запросов.

### Процессоры (Processors)
- `DataProcessor`: Класс для обработки данных через зарегистрированные процессоры.

### Диспетчеры (Dispatchers)
- `DtoQueueDispatcherInterface`: Интерфейс для диспетчеризации DTO в очередь.
- `MessengerQueueDispatcher`: Реализация с использованием Symfony Messenger.
- `NullQueueDispatcher`: Заглушка для случаев, когда диспетчеризация не требуется.

### Сериализация (Serialization)
- `SymfonyDtoDeserializer`: Реализация десериализатора на основе Symfony Serializer.

### Исключения (Exceptions)
- `RequestException`: Исключение для ошибок обработки запросов.

### Dependency Injection
- `AppLayerProcessorExtension`: Расширение для конфигурации контейнера зависимостей.

## Установка

Добавьте бандл в ваш проект через Composer:

```bash
composer require elrise/application-layer
```

Зарегистрируйте бандл в `config/bundles.php`:

```php
return [
    // ...
    Elrise\Bundle\AppLayerBundle\AppLayerBundle::class => ['all' => true],
];
```

## Использование

### 1. Создание DTO

Создайте класс DTO, например:

```php
<?php

namespace App\Dto;

class CreateUserDto
{
    public string $name;
    public string $email;
}
```

### 2. Создание обработчика запросов

Реализуйте `RequestHandlerInterface`:

```php
<?php

namespace App\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateUserHandler implements RequestHandlerInterface
{
    public function handle(Request $request, object $dto): object
    {
        // Логика обработки
        // $dto instanceof CreateUserDto
        // Возвращает результат (например, новый объект пользователя)
    }
}
```

### 3. Использование DtoRequestHandler

В контроллере или сервисе:

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
            dispatchToQueue: false // или true для асинхронной обработки
        );

        // Обработка результата
    }
}
```

### 4. Использование DataProcessor

Для обработки данных без DTO:

```php
<?php

use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class GetUserProcessor implements DataProcessorInterface
{
    public function process(Request $request): mixed
    {
        // Логика обработки данных
    }
}
```

В сервисе:

```php
use Elrise\Bundle\AppLayerBundle\Processor\DataProcessor;

$result = $this->dataProcessor->process($request, GetUserProcessor::class);
```

## Конфигурация

Бандл автоматически регистрирует сервисы с соответствующими тегами:
- `app_layer.dto_request_handler` для обработчиков запросов
- `app_layer.data_processor` для процессоров данных

Для асинхронной обработки требуется установка `symfony/messenger`.

## Тестирование

Бандл включает набор тестов. Запустите их с помощью PHPUnit:

```bash
./vendor/bin/phpunit
```

## Лицензия

Этот бандл распространяется под лицензией MIT.

## Авторы

- Alex (alexk@elrise.ru)