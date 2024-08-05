<?php

declare(strict_types=1);

use Rockett\Pipeline\Processors\ProcessorContract;
use Rockett\Pipeline\Processors\TapProcessor;

$stages = [
  fn ($traveler): int => $traveler + 2,
  fn ($traveler): int => $traveler * 10,
  fn ($traveler): int => $traveler * 10
];

test('it implements processor contract', function () {
  $tapProcessor = new TapProcessor();

  expect($tapProcessor)->toBeInstanceOf(
    ProcessorContract::class
  );
});

test('it handles before callbacks', function () use ($stages) {
  $beforeLogs = [];

  $tapProcessor = new TapProcessor(
    beforeCallback: function ($traveler) use (&$beforeLogs) {
      $beforeLogs[] = $traveler;
    }
  );

  $tapProcessor->process(1, ...$stages);

  expect($beforeLogs)->toBe([1, 3, 30]);
});

test('it handles after callbacks', function () use ($stages) {
  $afterLogs = [];

  $tapProcessor = new TapProcessor(
    afterCallback: function ($traveler) use (&$afterLogs) {
      $afterLogs[] = $traveler;
    }
  );

  $tapProcessor->process(1, ...$stages);

  expect($afterLogs)->toBe([3, 30, 300]);
});
