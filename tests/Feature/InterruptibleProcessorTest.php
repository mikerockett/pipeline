<?php

declare(strict_types=1);

/**
 * @deprecated InterruptibleProcessor is deprecated. Use Processor instead.
 */

use Rockett\Pipeline\Processors\InterruptibleProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

$stages = [
  fn($traveler): int => $traveler + 2,
  fn($traveler): int => $traveler * 10,
  fn($traveler): int => $traveler * 10
];

it('implements processor contract', function () {
  $processor = new InterruptibleProcessor(fn(): bool => false);
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

it('throws exception when callback is not callable', function () {
  new InterruptibleProcessor('not_callable');
})->throws(InvalidArgumentException::class, '$callback must be callable');

it('interrupts processing', function () use ($stages) {
  $result = (new InterruptibleProcessor(
    fn($traveler): bool => $traveler > 10
  ))->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('processes all stages when interrupt condition never met', function () use ($stages) {
  $processor = new InterruptibleProcessor(fn(): bool => false);
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(700);
});

it('interrupts processing using continue when', function () use ($stages) {
  $result = InterruptibleProcessor::continueWhen(
    fn($traveler): bool => $traveler < 10
  )->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('interrupts processing using continue unless', function () use ($stages) {
  $result = InterruptibleProcessor::continueUnless(
    fn($traveler): bool => $traveler > 10
  )->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('inverts conditioner logic with withInversedConditioner', function () use ($stages) {
  $processor = (new InterruptibleProcessor(fn($x): bool => $x < 100))
    ->withInversedConditioner();
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(700);
});

it('works with string callable functions', function () {
  $processor = new InterruptibleProcessor('is_null');
  $stages = [fn($x) => $x + 1, fn($x) => null, fn($x) => $x + 10];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBeNull();
});

it('handles complex interrupt logic', function () {
  $processor = new InterruptibleProcessor(fn($x): bool => $x % 3 === 0);
  $stages = [fn($x) => $x + 1, fn($x) => $x * 2];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(6);
});

it('works with array callable methods', function () {
  $obj = new class {
    public function isLarge($value): bool
    {
      return $value > 50;
    }
  };

  $processor = new InterruptibleProcessor([$obj, 'isLarge']);
  $result = $processor->process(5, fn($x) => $x + 2, fn($x) => $x * 10);
  expect($result)->toBe(70);
});

it('works with static method callable', function () {
  $processor = new InterruptibleProcessor(['Rockett\Pipeline\Processors\InterruptibleProcessor', 'continueWhen']);
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

it('handles immediate interruption on first stage', function () {
  $processor = new InterruptibleProcessor(fn($x): bool => $x >= 5);
  $stages = [fn($x) => $x + 10, fn($x) => $x * 2];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(15);
});

it('handles empty stages array', function () {
  $processor = new InterruptibleProcessor(fn(): bool => true);
  $result = $processor->process(42);
  expect($result)->toBe(42);
});

it('continueWhen and continueUnless produce same result with opposite conditions', function () use ($stages) {
  $result1 = InterruptibleProcessor::continueWhen(
    fn($x): bool => $x <= 10
  )->process(5, ...$stages);

  $result2 = InterruptibleProcessor::continueUnless(
    fn($x): bool => $x > 10
  )->process(5, ...$stages);

  expect($result1)->toBe($result2)->toBe(70);
});

it('preserves original processor when using withInversedConditioner', function () use ($stages) {
  $original = new InterruptibleProcessor(fn($x): bool => $x > 10);
  $inverted = $original->withInversedConditioner();

  expect($inverted)->toBe($original);

  $result = $inverted->process(5, ...$stages);
  expect($result)->toBe(7);
});

it('works with closure that captures external variables', function () use ($stages) {
  $threshold = 50;
  $processor = new InterruptibleProcessor(
    fn($x): bool => $x > $threshold
  );

  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('handles boolean return values correctly', function () {
  $processor = new InterruptibleProcessor(fn(): bool => true);
  $stages = [fn($x) => $x + 1];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(6);
});

it('handles truthy non-boolean return values', function () {
  $processor = new InterruptibleProcessor(fn($x): int => $x);
  $stages = [fn($x) => 0, fn($x) => $x + 10];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(10);
});
