# Zana Mediator

Send commands and queries to their single handler through pipeline behaviours
(CQRS). Requires PHP 8.5 or later.

```
composer require mj-zana/mediator
```

## Usage

```php
final readonly class CalculateBmi
{
    public function __construct(public string $patientId, public float $weightKg, public float $heightM) {}
}

final class CalculateBmiHandler
{
    public function __construct(private MeasurementRepository $measurements) {}

    public function __invoke(CalculateBmi $command): Measurement { /* ... */ }
}
```

Register `MediatorServiceProvider` and your handlers as services, then map the
handlers in a module's `boot()`:

```php
$container->get(HandlerMap::class)->register(CalculateBmiHandler::class, PatientQueryHandler::class);
```

```php
public function __construct(private MediatorInterface $mediator) {}

$measurement = $this->mediator->send(new CalculateBmi($id, 80.0, 1.8));
```

- **Mapping:** the message class comes from `#[Handles(Message::class)]` or the
  type of the handler's first parameter.
- **Lookup:** by the message's class, then its parent classes, then its interfaces.
- **Scope:** the mediator is scoped, so handlers resolve from the current request
  or command scope.
- **Behaviours** (`PipelineBehavior`, configured in `mediator.behaviors`) wrap
  every handler, the first being the outermost. They are useful for
  validation, transactions or logging.

## License

MIT — see [LICENSE](LICENSE).
