<?php

declare(strict_types=1);

use Rockett\Pipeline\Processors\InterruptibleProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

$stages = [
  fn ($traveler): int => $traveler + 2,
  fn ($traveler): int => $traveler * 10,
  fn ($traveler): int => $traveler * 10
];

test('it implements processor contract', function () {
  $processor = new InterruptibleProcessor(fn (): bool => false);
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

test('it interrupts processing', function () use ($stages) {
  $result = (new InterruptibleProcessor(
    fn ($traveler): bool => $traveler > 10
  ))->process(5, ...$stages);
  expect($result)->toBe(70);
});

test('it interrupts processing using continue when', function () use ($stages) {
  $result = InterruptibleProcessor::continueWhen(
    fn ($traveler): bool => $traveler < 10
  )->process(5, ...$stages);
  expect($result)->toBe(70);
});

test('it interrupts processing using continue unless', function () use ($stages) {
  $result = InterruptibleProcessor::continueUnless(
    fn ($traveler): bool => $traveler > 10
  )->process(5, ...$stages);
  expect($result)->toBe(70);
});
