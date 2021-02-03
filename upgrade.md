# Upgrade guide

## From 0.1.0 to 0.2.0

Callables don't have to be wrapped anymore.

```php
// BEFORE
$pipeline->pipe(CallableStage::forCallable(function ($traveler) {
    return $traveler;
}))->process($traveler);

// After
$pipeline->pipe(function ($traveler) {
    return $traveler;
})->process($traveler);
```

Class based stages now require to implement the `__invoke` method.

```php
// BEFORE
class MyStage implements StageInterface
{
    public function process($traveler)
    {
        return $traveler;
     }
}

// AFTER
class MyStage implements StageInterface
{
    public function __invoke($traveler)
    {
        return $traveler;
     }
}
```
