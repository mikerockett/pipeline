# Pipeline

![GitHub License](https://img.shields.io/github/license/mikerockett/pipeline)
![Packagist Version](https://img.shields.io/packagist/v/rockett/pipeline?label=Release)
![Packagist Downloads](https://img.shields.io/packagist/dm/rockett/pipeline?label=Installs)
![Forgejo Workflow Status](https://git.rockett.pw/rockett/pipeline/actions/workflows/tests.yml/badge.svg)

Provides an implementation of the [pipeline pattern](https://en.wikipedia.org/wiki/Pipeline_(software)) with additional processors for **conditional interruption** and **stage tapping**.

This package was originally forked from League's excellent [pipeline package](https://github.com/thephpleague/pipeline).

## Installation

```bash
composer require rockett/pipeline
```

Requires PHP 8.3+.

## Quick Start

```php
use Rockett\Pipeline\Pipeline;

$pipeline = (new Pipeline)
    ->pipe(fn($x) => $x * 2)
    ->pipe(fn($x) => $x + 1);

echo $pipeline->process(10); // Outputs: 21
```

> [!NOTE]
> **PHP 8.5+ Pipe Operator:** With PHP 8.5 introducing the native [pipe operator](https://php.watch/versions/8.5/pipe-operator), basic sequential operations can now be achieved without a package. However, this library still provides value through reusable pipeline objects, conditional interruption (`continueWhen`/`continueUnless`), stage tapping for observability (`beforeEach`/`afterEach`), and a fluent API for composing complex processing workflows that go beyond simple function chaining.

## Pipeline Pattern

The pipeline pattern lets you compose sequential operations by chaining *stages*. Each stage receives a *traveler* (payload), processes it, and passes the output to the next stage. Internally, this is equivalent to:

```php
$output = $stage3($stage2($stage1($traveler)));
```

## Immutability

Pipelines are implemented as immutable stage-chains, contracted by the `PipelineContract` interface. When you add a new stage, the pipeline will be cloned with the new stage added in. This makes pipelines easy to re-use, and minimizes side-effects.

## Usage

Operations in a pipeline (stages) can accept anything from the pipeline that satisfies the `callable` type-hint. So closures and anything that's invokable will work.

```php
$pipeline = (new Pipeline)->pipe(static function ($traveler) {
  return $traveler * 10;
});
```

## Class-based stages

Classes can be used as stages by implementing `StageContract` and an `__invoke` method:

```php
use Rockett\Pipeline\Pipeline;
use Rockett\Pipeline\Contracts\StageContract;

class TimesTwoStage implements StageContract
{
  public function __invoke($traveler)
  {
    return $traveler * 2;
  }
}

$pipeline = (new Pipeline)
  ->pipe(new TimesTwoStage)
  ->pipe(new PlusOneStage);

$pipeline->process(10); // Returns 21
```

You can create custom stage contracts to type-hint the traveler and return type.

## Re-usability

Pipelines can be re-used as stages within other pipelines, enabling composable architectures:

```php
$processApiRequest = (new Pipeline)
  ->pipe(new ExecuteHttpRequest) // B
  ->pipe(new ParseJsonResponse); // C

$pipeline = (new Pipeline)
  ->pipe(new ConvertToPsr7Request) // A
  ->pipe($processApiRequest) // (B and C)
  ->pipe(new ConvertToDataTransferObject); // D

$pipeline->process(new DeleteArticle($postId));
```

## Pipeline Builders

While pipelines are immutable by design, there are scenarios where you need to conditionally compose stages before building the pipeline. Pipeline builders solve this by providing a **mutable container** for collecting stages, which is then converted to an immutable pipeline:

```php
use Rockett\Pipeline\Builder\PipelineBuilder;

$builder = new PipelineBuilder;

$builder->add(new ValidateInput)
  ->add(new SanitizeData);

if ($config->get('logging.enabled')) {
  $builder->add(new LogRequest);
}

if ($user->hasPermission('admin')) {
  $builder->add(new EnrichWithAdminData);
}

$builder->add(new TransformToResponse)
  ->add(new CompressOutput);

$pipeline = $builder->build();
$result = $pipeline->process($request);
```

Once `build()` is called, you have an immutable pipeline that can be reused or passed around safely without concerns about side-effects from modifications.

## Processors

Processors handle iteration through stages and enable additional features like conditional interruption and stage tapping.

> [!CAUTION]
> **As of v4.1, these processors are deprecated and slated for removal in v5:**
> * **InterruptibleProcessor** – use **Processor** with `continueUnless()`/`continueWhen()`
> * **TapProcessor** – use **Processor** with `beforeEach()`/`afterEach()`
> * **InterruptibleTapProcessor** – use **Processor** with combined methods

### `FingersCrossedProcessor` (Default)

Basic sequential processing with no early exit capability (throw an exception to stop).

```php
use Rockett\Pipeline\Pipeline;
use Rockett\Pipeline\Processors\FingersCrossedProcessor;

$pipeline = new Pipeline(new FingersCrossedProcessor);
// Or simply: new Pipeline() – FingersCrossedProcessor is the default
```

### `Processor`

The `Processor` supports conditional interruption (early exit) and stage tapping (callbacks before/after each stage), configured fluently with method-chaining:

```php
use Rockett\Pipeline\Processors\Processor;

$processor = (new Processor())
    ->continueUnless(fn($traveler) => $traveler->hasError())
    ->beforeEach(fn($traveler) => $logger->info('Processing:', $traveler->toArray()))
    ->afterEach(fn($traveler) => $metrics->increment('pipeline.stage.completed'));

$pipeline = (new Pipeline($processor))
    ->pipe(new ValidateInput)
    ->pipe(new ProcessData)
    ->pipe(new SaveToDatabase);
```

Features can be composed via method chaining:
- `continueUnless(callable)` – exit when callback returns true
- `continueWhen(callable)` – exit when callback returns false
- `invert()` – invert the interrupt condition
- `beforeEach(callable)` – execute callback before each stage
- `afterEach(callable)` – execute callback after each stage

#### Exiting pipelines early

Use interrupt methods to exit pipelines early based on conditions:

```php
use Rockett\Pipeline\Processors\Processor;

$processor = (new Processor())
    ->continueUnless(fn($traveler) => $traveler->hasError());

$pipeline = (new Pipeline($processor))
    ->pipe(new ValidateInput)
    ->pipe(new ProcessData)
    ->pipe(new SaveToDatabase);

$output = $pipeline->process($request);
```

In this example, when `$traveler->hasError()` returns true, the pipeline exits early.

**Available interrupt methods:**

```php
// Exit when condition is true
$processor = (new Processor())
    ->continueUnless(fn($traveler) => $traveler->hasError());

// Exit when condition becomes false
$processor = (new Processor())
    ->continueWhen(fn($traveler) => $traveler->isValid());

// Invert the condition
$processor = (new Processor())
    ->continueWhen(fn($traveler) => $traveler->isValid())
    ->invert(); // Now exits when isValid() returns false
```

#### Invoking actions on each stage

Use tap methods to invoke callbacks before and/or after each stage for logging, metrics, or debugging:

```php
use Rockett\Pipeline\Processors\Processor;

$processor = (new Processor())
    ->beforeEach(fn($traveler) => $logger->info('Processing:', $traveler->toArray()))
    ->afterEach(fn($traveler) => $metrics->increment('pipeline.stage.completed'));

$pipeline = (new Pipeline($processor))
    ->pipe(new StageOne)
    ->pipe(new StageTwo)
    ->pipe(new StageThree);

$output = $pipeline->process($traveler);
```

#### Per-stage conditions

Stages can optionally implement a `condition` method to control whether they should execute. If the condition returns false, the stage is skipped and the traveler is passed to the next stage untouched.

```php
class ProcessPaymentStage implements StageContract
{
  public function condition($traveler): bool
  {
    return $traveler->requiresPayment();
  }

  public function __invoke($traveler)
  {
    return $traveler->processPayment();
  }
}
```

> [!NOTE]
> Condition-checking is done after the `beforeEach` stage tap.

## Handling Exceptions

The package won't catch exceptions. Handle them in your code, either inside a stage or when calling the pipeline.

```php
$pipeline = (new Pipeline)->pipe(
  static fn () => throw new LogicException
);

try {
  $pipeline->process($traveler);
} catch(LogicException $e) {
  // Handle the exception.
}
```

## Testing

```bash
composer test
```

## License

Pipeline is licensed under the permissive [MIT license](license.md).

## Contributing

Contributions are welcome – if you have something to add to this package, or have found a bug, feel free to submit a merge request for review.
