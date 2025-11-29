<?php

declare(strict_types=1);

/**
 * @deprecated TapProcessor is deprecated. Use Processor instead.
 */

use Rockett\Pipeline\Processors\ProcessorContract;
use Rockett\Pipeline\Processors\TapProcessor;

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
  $tapProcessor = new TapProcessor(fn() => print 'tap!');

  expect($tapProcessor)->toBeInstanceOf(
    ProcessorContract::class
  );
});

it('throws exception when both callbacks are null', function () {
  new TapProcessor();
})->throws(
  InvalidArgumentException::class,
  'At least one of $beforeCallback and $afterCallback must be provided'
);

it('throws exception when before callback is not callable', function () {
  new TapProcessor('not_callable');
})->throws(
  InvalidArgumentException::class,
  '$beforeCallback must be callable'
);

it('throws exception when after callback is not callable', function () {
  new TapProcessor(null, 'not_callable');
})->throws(
  InvalidArgumentException::class,
  '$afterCallback must be callable'
);

it('handles before callbacks', function () use ($stages, &$tapResults) {
  $tapProcessor = new TapProcessor(
    beforeCallback: function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    }
  );

  $result = $tapProcessor->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe([1, 3, 30]);
});

it('handles after callbacks', function () use ($stages, &$tapResults) {
  $tapProcessor = new TapProcessor(
    afterCallback: function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    }
  );

  $result = $tapProcessor->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe([3, 30, 300]);
});

it('handles both callbacks together', function () use ($stages, &$tapResults) {
  $tapProcessor = new TapProcessor(
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $tapProcessor->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe([
    'before: 1',
    'after: 3',
    'before: 3',
    'after: 30',
    'before: 30',
    'after: 300'
  ]);
});

it('updates before callback with beforeEach method', function () use ($stages, &$tapResults) {
  $tapProcessor = new TapProcessor(
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "original: $traveler";
    }
  );

  $tapProcessor->beforeEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "updated: $traveler";
  });

  $result = $tapProcessor->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe(['updated: 1', 'updated: 3', 'updated: 30']);
});

it('updates after callback with afterEach method', function () use ($stages, &$tapResults) {
  $tapProcessor = new TapProcessor(
    null,
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "original: $traveler";
    }
  );

  $tapProcessor->afterEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "updated: $traveler";
  });

  $result = $tapProcessor->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe(['updated: 3', 'updated: 30', 'updated: 300']);
});

it('chains method calls fluently', function () use ($stages, &$tapResults) {
  $result = (new TapProcessor(fn() => null))
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    })
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    })
    ->process(1, ...$stages);

  expect($result)->toBe(300);
  expect($tapResults)->toBe([
    'before: 1',
    'after: 3',
    'before: 3',
    'after: 30',
    'before: 30',
    'after: 300'
  ]);
});

it('handles empty stages array', function () use (&$tapResults) {
  $tapProcessor = new TapProcessor(
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $tapProcessor->process(42);

  expect($result)->toBe(42);
  expect($tapResults)->toBe([]);
});

it('works with string callable functions', function () use (&$tapResults) {
  $tapProcessor = new TapProcessor(
    'is_null',
    function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    }
  );

  $result = $tapProcessor->process(5, fn($x) => $x * 2);

  expect($result)->toBe(10);
  expect($tapResults)->toBe([10]);
});

it('works with array callable methods', function () use (&$tapResults) {
  $logger = new class {
    public function log($value): void {}
  };

  $tapProcessor = new TapProcessor(
    [$logger, 'log'],
    function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    }
  );

  $result = $tapProcessor->process(5, fn($x) => $x + 3);

  expect($result)->toBe(8);
  expect($tapResults)->toBe([8]);
});

it('works with closure capturing external variables', function () use ($stages, &$tapResults) {
  $prefix = 'log';

  $tapProcessor = new TapProcessor(
    function ($traveler) use (&$tapResults, $prefix) {
      $tapResults[] = "$prefix: $traveler";
    }
  );

  $result = $tapProcessor->process(1, fn($x) => $x + 1);

  expect($result)->toBe(2);
  expect($tapResults)->toBe(['log: 1']);
});

it('preserves processor instance when using fluent methods', function () {
  $original = new TapProcessor(fn() => null);
  $updated = $original->beforeEach(fn() => null);

  expect($updated)->toBe($original);
});

it('handles single stage correctly', function () use (&$tapResults) {
  $tapProcessor = new TapProcessor(
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    },
    function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    }
  );

  $result = $tapProcessor->process(10, fn($x) => $x / 2);

  expect($result)->toBe(5);
  expect($tapResults)->toBe(['before: 10', 'after: 5']);
});
