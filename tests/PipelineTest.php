<?php

declare(strict_types=1);

use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Contracts\StageContract;
use Rockett\Pipeline\Pipeline;

test('it implements pipeline contract', function () {
  $pipeline = new Pipeline;
  expect($pipeline)->toBeInstanceOf(PipelineContract::class);
});

test('it instantiates with private properties', function () {
  $pipeline = new Pipeline;
  $instanceReflection = new ReflectionClass($pipeline);
  expect($instanceReflection->hasProperty($processorProperty = 'processor'))->toBeTrue();
  expect($instanceReflection->getProperty($processorProperty)->isPrivate())->toBeTrue();
  expect($instanceReflection->hasProperty($stagesProperty = 'stages'))->toBeTrue();
  expect($instanceReflection->getProperty($stagesProperty)->isPrivate())->toBeTrue();
});

test('it is immutable when piping', function () {
  $pipeline = new Pipeline;
  $withPipe = $pipeline->pipe(fn (): int => 1);
  expect($pipeline)->not->toBe($withPipe);
});

test('it pipes operations', function () {
  $result = (new Pipeline)
    ->pipe(fn (): int => 1)
    ->process('1');
  expect($result)->toBe(1);
});

test('it processes payloads', function () {
  $result = (new Pipeline)
    ->pipe(fn ($traveler): int => $traveler + 1)
    ->process(1);
  expect($result)->toBe(2);
});

test('it processes payloads in sequence', function () {
  $result = (new Pipeline)
    ->pipe(fn ($traveler): int => $traveler + 2)
    ->pipe(fn ($traveler): int => $traveler * 10)
    ->process(1);
  expect($result)->toBe(30);
});

test('it processes payloads using stage contract implementations', function () {
  $result = (new Pipeline)
    ->pipe(new class implements StageContract
    {
      public function __invoke($traveler)
      {
        return strrev($traveler);
      }
    })
    ->process('payload');
  expect($result)->toBe(strrev('payload'));
});
