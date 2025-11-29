<?php

declare(strict_types=1);

/**
 * @deprecated InterruptibleTapProcessor is deprecated. Use Processor instead.
 */

use Rockett\Pipeline\Processors\InterruptibleTapProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

$stages = [
  fn($traveler): int => $traveler + 2,
  fn($traveler): int => $traveler * 10,
  fn($traveler): int => $traveler * 10
];

$tapResults = [];

beforeEach(function () use (&$tapResults) {
  $tapResults = [];
});

it('implements processor contract', function () {
  $processor = new InterruptibleTapProcessor(
    fn(): bool => false,
    fn($x) => null
  );
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

it('throws exception when interrupt callback is not callable', function () {
  new InterruptibleTapProcessor('not_callable');
})->throws(
  InvalidArgumentException::class,
  '$callback must be callable'
);

it('throws exception when before callback is not callable', function () {
  new InterruptibleTapProcessor(
    fn(): bool => false,
    'not_callable'
  );
})->throws(
  InvalidArgumentException::class,
  '$beforeCallback must be callable'
);

it('throws exception when after callback is not callable', function () {
  new InterruptibleTapProcessor(
    fn(): bool => false,
    null,
    'not_callable'
  );
})->throws(
  InvalidArgumentException::class,
  '$afterCallback must be callable'
);

it('throws exception when both tap callbacks are null', function () {
  new InterruptibleTapProcessor(fn(): bool => false);
})->throws(
  InvalidArgumentException::class,
  'At least one of $beforeCallback and $afterCallback must be provided'
);

it('processes with interruption and before callback', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn(): bool => false,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    }
  );
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(700);
  expect($tapResults)->toBe(['before: 5', 'before: 7', 'before: 70']);
});

it('processes with before callback only', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler > 100,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe(['before: 5', 'before: 7', 'before: 70']);
});

it('processes with after callback only', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler > 1000,
    null,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe(['after: 7', 'after: 70', 'after: 700']);
});

it('processes with both callbacks and interruption', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler > 50,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(70);
  expect($tapResults)->toBe(['before: 5', 'after: 7', 'before: 7', 'after: 70']);
});

it('uses inverted conditioner correctly', function () use ($stages, &$tapResults) {
  $processor = (new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler < 100,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    }
  ))->withInversedConditioner();

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe(['before: 5', 'before: 7', 'before: 70']);
});

it('creates processor with continueUnless static method', function () use ($stages, &$tapResults) {
  $processor = InterruptibleTapProcessor::continueUnless(
    fn($traveler): bool => $traveler > 10,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(70);
  expect($tapResults)->toBe(['before: 5', 'before: 7']);
});

it('creates processor with continueWhen static method', function () use ($stages, &$tapResults) {
  $processor = InterruptibleTapProcessor::continueWhen(
    fn($traveler): bool => $traveler < 100,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe(['before: 5', 'before: 7', 'before: 70']);
});

it('updates before callback with beforeEach method', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler > 1000,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "original: $traveler";
    }
  );

  $processor->beforeEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "before: $traveler";
  });

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe(['before: 5', 'before: 7', 'before: 70']);
});

it('updates after callback with afterEach method', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler > 1000,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "original: $traveler";
    }
  );

  $processor->afterEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "after: $traveler";
  });

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe([
    'original: 5',
    'after: 7',
    'original: 7',
    'after: 70',
    'original: 70',
    'after: 700'
  ]);
});

it('chains method calls fluently', function () use ($stages, &$tapResults) {
  $processor = InterruptibleTapProcessor::continueUnless(
    fn($traveler): bool => $traveler > 50,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "initial: $traveler";
    }
  )->beforeEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "before: $traveler";
  })
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    });

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(70);
  expect($tapResults)->toBe(['before: 5', 'after: 7', 'before: 7', 'after: 70']);
});

it('works with complex interrupt conditions', function () use (&$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler % 3 === 0,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "processing: $traveler";
    }
  );

  $stages = [
    fn($x) => $x + 1,  // 5 -> 6
    fn($x) => $x * 2,  // 6 -> 12 (but won't reach here because 6 % 3 === 0)
    fn($x) => $x + 10  // should not execute
  ];

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(6);  // Interrupts at 6, not 12
  expect($tapResults)->toBe(['processing: 5']);  // Only processes 5, then 5+1=6 triggers interrupt
});

it('handles string callable interrupt conditions', function () use (&$tapResults) {
  $processor = new InterruptibleTapProcessor(
    'is_null',
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "value: $traveler";
    }
  );

  $stages = [
    fn($x) => $x + 1,  // 5 -> 6
    fn($x) => null,    // 6 -> null (triggers interrupt)
    fn($x) => $x + 10  // should not execute
  ];

  $result = $processor->process(5, ...$stages);

  expect($result)->toBeNull();
  expect($tapResults)->toBe(['value: 5', 'value: 6']);  // Processes 5, then 6, then null interrupts
});

it('processes all stages when interrupt condition never met', function () use ($stages, &$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn(): bool => false,  // Never interrupt
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(700);
  expect($tapResults)->toBe([
    'before: 5',
    'after: 7',
    'before: 7',
    'after: 70',
    'before: 70',
    'after: 700'
  ]);
});

it('returns correct values with array destructuring', function () use (&$tapResults) {
  $processor = new InterruptibleTapProcessor(
    fn($traveler): bool => $traveler >= 15,  // Changed to >= so it interrupts at 15
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "start: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "end: $traveler";
    }
  );

  $stages = [fn($x) => $x * 2, fn($x) => $x + 5];
  $result = $processor->process(5, ...$stages);

  expect($result)->toBe(15);
  expect($tapResults)->toBe(['start: 5', 'end: 10', 'start: 10', 'end: 15']);
});
