# Rockett\Pipeline

![GitHub License](https://img.shields.io/github/license/mikerockett/pipeline?style=for-the-badge)
![Packagist Version](https://img.shields.io/packagist/v/rockett/pipeline?label=Release&style=for-the-badge)
![Packagist Downloads](https://img.shields.io/packagist/dm/rockett/pipeline?label=Installs&style=for-the-badge)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/mikerockett/pipeline/tests?label=Tests&style=for-the-badge)

Built atop [League’s excellent package](https://github.com/thephpleague/pipeline), `Rockett\Pipeline` provides an implementation of the [pipeline pattern](https://en.wikipedia.org/wiki/Pipeline_(software)).

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

Operations in a pipeline (stages) can accept anything from the pipeline that satisfies the `callable` type-hint. So closures and anything that’s invokable will work.

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

When stages are piped through a pipeline, they are done so using a processor, which is responsible for iterating through each stage and piping it into the owning pipeline. There are three available processors:

* `FingersCrossedProcessor` (this is the default)
* `InterruptibleProcessor`
* `TapProcessor`

It goes without saying that the default processor only iterates and pipes stages. It does nothing else, and there is no way to exit the pipeline without throwing an exception.

### Exiting pipelines early

The `InterruptibleProcessor`, on the other hand, provides a mechanism that allows you to exit the pipeline early, if so required. This is done by way of a `callable` that is invoked at every stage as a condition to continuing the pipeline:

```php
use Rockett\Pipeline\Processors\InterruptibleProcessor;

$processor = new InterruptibleProcessor(
  static fn ($traveler) => $traveler->somethingIsntRight()
);

$pipeline = (new Pipeline($processor))
  ->pipe(new SafeStage)
  ->pipe(new UnsafeStage)
  ->pipe(new AnotherSafeStage);

$output = $pipeline->process($traveler);
```

In this example, the callable passed to the processor will check to see if something isn’t right and, if so, it will return `true`, causing the processor exit the pipeline and return the traveler as the output.

You can also use the `continueUnless` helper to instantiate the interruptible processor:

```php
$processor = InterruptibleProcessor::continueUnless(
  static fn ($traveler) => $traveler->somethingIsntRight()
);
```

If you would like to reverse the condition and only continue when the callable returns true, you can use the `continueWhen` helper instead:

```php
$processor = InterruptibleProcessor::continueWhen(
  static fn ($traveler) => $traveler->everythingIsFine()
);
```

### Invoking actions on each stage

Using the `TapProcessor`, you can invoke an action before and/or after a stage is piped through a pipeline. This can be useful if you would like to handle common side-effects outside of each stage, such as logging or broadcasting.

The processor takes two callables:

```php
use Rockett\Pipeline\Processors\TapProcessor;

// Define and instantiate a $logger and a $broadcaster …

$processor = new TapProcessor(
  // $beforeEach, called before a stage is piped
  static fn ($traveler) => $logger->info('Traveller passing through pipeline:', $traveler->toArray()),

  // $afterEach, called after a stage is piped and the output captured
  static fn ($traveler) => $broadcaster->broadcast($users, 'Something happened', $traveler)
);

$pipeline = (new Pipeline($processor))
  ->pipe(new StageOne)
  ->pipe(new StageTwo)
  ->pipe(new StageThree);

$output = $pipeline->process($traveler);
```

Both of these callables are **optional**. By excluding both, the processor will act in the exact same way as the default `FingersCrossedProcessor`.

If you would like to pass only one callback, then you can use the helper methods:

```php
$processor = (new TapProcessor)->beforeEach(/** callable **/); // or …
$processor = (new TapProcessor)->afterEach(/** callable **/);
```

You can also chain them as an alternative to using the constructor:

```php
$processor = (new TapProcessor)
  ->beforeEach(/** callable **/)
  ->afterEach(/** callable **/);
```

If you are using PHP 8 or higher, it is encouraged that you use [named arguments](https://stitcher.io/blog/php-8-named-arguments) instead:

```php
$processor = new TapProcessor(
  beforeEach: /** optional callable **/,
  afterEach: /** optional callable **/,
)
```

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

## License

Pipeline is licensed under the permissive [MIT license](license.md).

## Contributing

Contributions are welcome – if you have something to add to this package, or have found a bug, feel free to submit a merge request for review.
