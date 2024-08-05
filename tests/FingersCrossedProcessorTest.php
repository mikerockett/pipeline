<?php

declare(strict_types=1);

use Rockett\Pipeline\Processors\FingersCrossedProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

test('it implements processor contract', function () {
  $processor = new FingersCrossedProcessor;
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

test('it processes stages', function () {
  $result = (new FingersCrossedProcessor)->process(
    1,
    fn ($traveler): int => $traveler * 2
  );
  expect($result)->toBe(2);
});
