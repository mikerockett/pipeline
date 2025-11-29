<?php

declare(strict_types=1);

use Rockett\Pipeline\Processors\Processor;
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
  $processor = new Processor();
  expect($processor)->toBeInstanceOf(ProcessorContract::class);
});

it('processes all stages sequentially', function () use ($stages) {
  $processor = new Processor();
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(700);
});

it('handles empty stages array', function () {
  $processor = new Processor();
  $result = $processor->process(42);
  expect($result)->toBe(42);
});

it('interrupts processing with continueUnless', function () use ($stages) {
  $processor = (new Processor())
    ->continueUnless(fn($traveler): bool => $traveler > 10);
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('interrupts processing with continueWhen', function () use ($stages) {
  $processor = (new Processor())
    ->continueWhen(fn($traveler): bool => $traveler < 10);
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
});

it('inverts condition with invert method', function () use ($stages) {
  $processor = (new Processor())
    ->continueWhen(fn($x): bool => $x < 100)
    ->invert();
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(7);
});

it('executes before callbacks', function () use ($stages, &$tapResults) {
  $processor = (new Processor())
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    });
  $result = $processor->process(1, ...$stages);
  expect($result)->toBe(300);
  expect($tapResults)->toBe([1, 3, 30]);
});

it('executes after callbacks', function () use ($stages, &$tapResults) {
  $processor = (new Processor())
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = $traveler;
    });
  $result = $processor->process(1, ...$stages);
  expect($result)->toBe(300);
  expect($tapResults)->toBe([3, 30, 300]);
});

it('executes both before and after callbacks', function () use ($stages, &$tapResults) {
  $processor = (new Processor())
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    })
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    });
  $result = $processor->process(1, ...$stages);
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

it('combines interrupt and tap features', function () use ($stages, &$tapResults) {
  $processor = (new Processor())
    ->continueUnless(fn($traveler): bool => $traveler > 50)
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    })
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    });
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
  expect($tapResults)->toBe(['before: 5', 'after: 7', 'before: 7', 'after: 70']);
});

it('chains all methods fluently', function () use ($stages, &$tapResults) {
  $processor = (new Processor())
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "log: $traveler";
    })
    ->continueUnless(fn($traveler): bool => $traveler >= 70)
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "done: $traveler";
    });
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
  expect($tapResults)->toBe(['log: 5', 'done: 7', 'log: 7', 'done: 70']);
});

it('preserves processor instance when using fluent methods', function () {
  $original = new Processor();
  $chained = $original->beforeEach(fn() => null)->afterEach(fn() => null);
  expect($chained)->toBe($original);
});

it('handles immediate interruption on first stage', function () {
  $processor = (new Processor())
    ->continueUnless(fn($x): bool => $x >= 5);
  $stages = [fn($x) => $x + 10, fn($x) => $x * 2];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(15);
});

it('works with string callable functions', function () {
  $processor = (new Processor())
    ->continueUnless('is_null');
  $stages = [fn($x) => $x + 1, fn($x) => null, fn($x) => $x + 10];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBeNull();
});

it('works with closure capturing external variables', function () use ($stages, &$tapResults) {
  $threshold = 50;
  $prefix = 'log';
  $processor = (new Processor())
    ->continueUnless(fn($x): bool => $x > $threshold)
    ->beforeEach(function ($traveler) use (&$tapResults, $prefix) {
      $tapResults[] = "$prefix: $traveler";
    });
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(70);
  expect($tapResults)->toBe(['log: 5', 'log: 7']);
});

it('handles complex interrupt logic with modulo', function () {
  $processor = (new Processor())
    ->continueUnless(fn($x): bool => $x % 3 === 0);
  $stages = [fn($x) => $x + 1, fn($x) => $x * 2];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(6);
});

it('allows updating callbacks after creation', function () use (&$tapResults) {
  $processor = new Processor();
  $processor->beforeEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "first: $traveler";
  });
  $processor->beforeEach(function ($traveler) use (&$tapResults) {
    $tapResults[] = "second: $traveler";
  });
  $result = $processor->process(5, fn($x) => $x + 1);
  expect($result)->toBe(6);
  expect($tapResults)->toBe(['second: 5']);
});

it('allows updating interrupt condition', function () use ($stages) {
  $processor = (new Processor())
    ->continueUnless(fn($x): bool => $x > 10);
  $result1 = $processor->process(5, ...$stages);
  expect($result1)->toBe(70);

  $processor->continueWhen(fn($x): bool => $x < 100);
  $result2 = $processor->process(5, ...$stages);
  expect($result2)->toBe(700);
});

it('handles truthy non-boolean interrupt values', function () {
  $processor = (new Processor())
    ->continueUnless(fn($x): int => $x);
  $stages = [fn($x) => 0, fn($x) => $x + 10];
  $result = $processor->process(5, ...$stages);
  expect($result)->toBe(10);
});

it('works without any configuration', function () {
  $processor = new Processor();
  $result = $processor->process(10, fn($x) => $x * 2, fn($x) => $x + 5);
  expect($result)->toBe(25);
});

it('handles single stage correctly', function () use (&$tapResults) {
  $processor = (new Processor())
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    })
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    });
  $result = $processor->process(10, fn($x) => $x / 2);
  expect($result)->toBe(5);
  expect($tapResults)->toBe(['before: 10', 'after: 5']);
});

it('skips stage when condition returns false', function () {
  $processor = new Processor();

  $stage1 = new class {
    public function condition($traveler): bool
    {
      return false;
    }

    public function __invoke($traveler)
    {
      return $traveler * 10;
    }
  };

  $result = $processor->process(5, $stage1, fn($x) => $x + 1);
  expect($result)->toBe(6);
});

it('executes stage when condition returns true', function () {
  $processor = new Processor();

  $stage1 = new class {
    public function condition($traveler): bool
    {
      return true;
    }

    public function __invoke($traveler)
    {
      return $traveler * 10;
    }
  };

  $result = $processor->process(5, $stage1, fn($x) => $x + 1);
  expect($result)->toBe(51);
});

it('handles multiple stages with mixed conditions', function () {
  $processor = new Processor();

  $stage1 = new class {
    public function condition($traveler): bool
    {
      return true;
    }

    public function __invoke($traveler)
    {
      return $traveler * 2;
    }
  };

  $stage2 = new class {
    public function condition($traveler): bool
    {
      return false;
    }

    public function __invoke($traveler)
    {
      return $traveler + 100;
    }
  };

  $stage3 = new class {
    public function condition($traveler): bool
    {
      return true;
    }

    public function __invoke($traveler)
    {
      return $traveler + 5;
    }
  };

  $result = $processor->process(5, $stage1, $stage2, $stage3);
  expect($result)->toBe(15);
});

it('executes beforeEach before condition check', function () use (&$tapResults) {
  $processor = (new Processor())
    ->beforeEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "before: $traveler";
    });

  $stage = new class {
    public function condition($traveler): bool
    {
      return false;
    }

    public function __invoke($traveler)
    {
      return $traveler * 10;
    }
  };

  $processor->process(5, $stage, fn($x) => $x + 1);
  expect($tapResults)->toBe(['before: 5', 'before: 5']);
});

it('skips afterEach when stage condition is false', function () use (&$tapResults) {
  $processor = (new Processor())
    ->afterEach(function ($traveler) use (&$tapResults) {
      $tapResults[] = "after: $traveler";
    });

  $stage1 = new class {
    public function condition($traveler): bool
    {
      return false;
    }

    public function __invoke($traveler)
    {
      return $traveler * 10;
    }
  };

  $stage2 = fn($x) => $x + 1;

  $processor->process(5, $stage1, $stage2);
  expect($tapResults)->toBe(['after: 6']);
});
