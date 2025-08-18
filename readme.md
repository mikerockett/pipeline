# Rockett\Pipeline

![GitHub License](https://img.shields.io/github/license/mikerockett/pipeline?style=for-the-badge)
![Packagist Version](https://img.shields.io/packagist/v/rockett/pipeline?label=Release&style=for-the-badge)
![Packagist Downloads](https://img.shields.io/packagist/dm/rockett/pipeline?label=Installs&style=for-the-badge)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/mikerockett/pipeline/test.yml?label=Tests&style=for-the-badge)

Built atop [League's excellent package](https://github.com/thephpleague/pipeline), `Rockett\Pipeline` provides an implementation of the [pipeline pattern](https://en.wikipedia.org/wiki/Pipeline_(software)) with additional processors for **conditional interruption** and **stage tapping**.

## Requirements

- PHP 8.3+

## Installation

```bash
composer require rockett/pipeline
```

## Quick Start

```php
use Rockett\Pipeline\Pipeline;

$pipeline = (new Pipeline)
    ->pipe(fn($x) => $x * 2)
    ->pipe(fn($x) => $x + 1);

echo $pipeline->process(10); // Outputs: 21
```

## Table of Contents

- [Pipeline Pattern](#pipeline-pattern)
- [Immutability](#immutability)
- [Usage](#usage)
- [Class-based stages](#class-based-stages)
- [Re-usability](#re-usability)
- [Pipeline Builders](#pipeline-builders)
- [Processors](#processors)
- [Handling Exceptions](#handling-exceptions)

## Pipeline Pattern

The pipeline pattern allows you to easily compose sequential operations by chaining stages. A *pipeline* consists of zero, one or more *stages*. A pipeline can process a payload, known as a *traveler*. When the pipeline is processed, the traveler will be passed to the first stage. From that moment on, the resulting output is passed on from stage to stage.

In the simplest form, the execution-chain can be represented as a `foreach` loop:

```php
$output = $traveler;

foreach ($stages as $stage) {
  $output = $stage($output);
}

return $output;
```

Effectively, this is the equivalent of:

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

As stages accept callables, class-based stages are also possible. The `StageContract` can be implemented on each stage, ensuring that you have the correct method-signature for the `__invoke` method.

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

class PlusOneStage implements StageContract
{
  public function __invoke($traveler)
  {
    return $traveler + 1;
  }
}

$pipeline = (new Pipeline)
  ->pipe(new TimesTwoStage)
  ->pipe(new PlusOneStage);

$pipeline->process(10); // Returns 21
```

You are free to create your own stage contract, should you wish to type-hint the traveler and set a return-type (this is useful, and recommended, when the type of data being injected into and returned from a stage must always remain the same).

## Re-usability

Because the `PipelineContract` is an extension of the `StageContract`, pipelines can be re-used as stages. This creates a highly-composable model to create complex execution-patterns, whilst keeping the cognitive-load low.

For example, if you want to compose a pipeline to process API calls, you would create something like this:

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

Here, we create a pipeline that processes an API request (single-responsibility), and compose it into a pipeline that deletes a news article.

## Pipeline Builders

Because pipelines themselves are immutable, pipeline builders are introduced to facilitate distributed-composition of a pipeline. These builders collect stages in advance and then allow you to create a pipeline at any given time.

```php
use Rockett\Pipeline\Builder\PipelineBuilder;

// Prepare the builder
$pipelineBuilder = (new PipelineBuilder)
  ->add(new LogicalStage)
  ->add(new AnotherStage)
  ->add(new FinalStage);

// Do other work …

// Build the pipeline
$pipeline = $pipelineBuilder->build();
```

## Processors

**This is where Rockett\Pipeline extends League's package** – when stages are piped through a pipeline, they are done so using a processor, which is responsible for iterating through each stage and piping it into the owning pipeline. There are four available processors:

* `FingersCrossedProcessor` (this is the default)
* `InterruptibleProcessor` – Exit pipelines early based on conditions
* `TapProcessor` – Execute callbacks before/after each stage (requires at least one callback)
* `InterruptibleTapProcessor` – Combines both interruption and tapping (requires at least one tap callback)

The default processor only iterates and pipes stages. It does nothing else, and there is no way to exit the pipeline without throwing an exception.

### Exiting pipelines early

The `InterruptibleProcessor` provides a mechanism that allows you to exit the pipeline early, if so required. This is done by way of a `callable` that is invoked at every stage as a condition to continuing the pipeline:

```php
use Rockett\Pipeline\Processors\InterruptibleProcessor;

$processor = new InterruptibleProcessor(
  fn($traveler) => $traveler->hasError()
);

$pipeline = (new Pipeline($processor))
  ->pipe(new ValidateInput)
  ->pipe(new ProcessData)
  ->pipe(new SaveToDatabase);

$output = $pipeline->process($request);
```

In this example, the callable will check if the traveler has an error and, if so, it will return `true`, causing the processor to exit the pipeline early and return the current traveler as the output.

**Helper methods:**

```php
// Exit when condition is true
$processor = InterruptibleProcessor::continueUnless(
  fn($traveler) => $traveler->hasError()
);

// Exit when condition becomes false
$processor = InterruptibleProcessor::continueWhen(
  fn($traveler) => $traveler->isValid()
);
```

### Invoking actions on each stage

Using the `TapProcessor`, you can invoke an action before and/or after a stage is piped through a pipeline. This is useful for cross-cutting concerns like logging, metrics, or debugging.

```php
use Rockett\Pipeline\Processors\TapProcessor;

$processor = new TapProcessor(
  beforeCallback: fn($traveler) => $logger->info('Processing:', $traveler->toArray()),
  afterCallback: fn($traveler) => $metrics->increment('pipeline.stage.completed')
);

$pipeline = (new Pipeline($processor))
  ->pipe(new StageOne)
  ->pipe(new StageTwo)
  ->pipe(new StageThree);

$output = $pipeline->process($traveler);
```

**At least one callback is required.** You can also use fluent methods:

```php
$processor = (new TapProcessor)
  ->beforeEach(fn($traveler) => $logger->debug('Before:', $traveler))
  ->afterEach(fn($traveler) => $logger->debug('After:', $traveler));
```

### Combining interruption and tapping

The `InterruptibleTapProcessor` combines both features:

```php
use Rockett\Pipeline\Processors\InterruptibleTapProcessor;

$processor = new InterruptibleTapProcessor(
  interruptCallback: fn($traveler) => $traveler->shouldStop(),
  beforeCallback: fn($traveler) => $logger->info('Processing stage'),
  afterCallback: fn($traveler) => $metrics->increment('stage.completed')
);

// Or using static factory methods (tap callbacks required)
$processor = InterruptibleTapProcessor::continueUnless(
  fn($traveler) => $traveler->hasError(),
  beforeCallback: fn($traveler) => $logger->debug('Before stage')
);

// Or using fluent interface
$processor = InterruptibleTapProcessor::continueWhen(
  fn($traveler) => $traveler->isValid(),
  afterCallback: fn($traveler) => $logger->debug('After stage')
)->beforeEach(fn($traveler) => $logger->debug('Before stage'));
```

> [!NOTE]
> This will likely become the default processor in a future release.

> [!TIP]
> The `InterruptibleTapProcessor` is particularly useful for complex pipelines where you need both conditional logic and observability.

## Handling Exceptions

This package is completely transparent when it comes exceptions and other throwables – it will not catch an exception or silence an error.

You need to catch these in your code, either inside a stage or at the time the pipeline is called to process a payload.

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
