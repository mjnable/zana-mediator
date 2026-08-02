# Zana Mediator

Mediator / message bus for the Zana framework.

## Features

- **`MediatorInterface`** / **`Mediator`** — dispatch commands and queries to registered handlers
- **Convention-based dispatch** — finds handlers by naming convention when no explicit mapping is given
- **Custom handler mappings** — register explicit command/query → handler pairs
- **Configurable constructor** — optional container-based handler resolution

## Install

```
composer require zana/mediator
```

## Usage

```php
use Zana\Mediator\Mediator;

$mediator = new Mediator([
    MyCommand::class => MyCommandHandler::class,
]);

$result = $mediator->dispatch(new MyCommand('payload'));
```

## Testing

```
vendor/bin/phpunit packages/mediator/tests
```

## License

MIT — see [LICENSE](LICENSE).

