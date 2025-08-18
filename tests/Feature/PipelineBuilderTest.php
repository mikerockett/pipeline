<?php

declare(strict_types=1);

use Rockett\Pipeline\Builder\PipelineBuilder;
use Rockett\Pipeline\Builder\PipelineBuilderContract;

it('implements pipeline builder contract', function () {
  $pipelineBuilder = new PipelineBuilder;
  expect($pipelineBuilder)->toBeInstanceOf(PipelineBuilderContract::class);
});

it('instantiates with private properties', function () {
  $pipelineBuilder = new PipelineBuilder;
  $instanceReflection = new ReflectionClass($pipelineBuilder);
  expect($instanceReflection->hasProperty($stagesProperty = 'stages'))->toBeTrue();
  expect($instanceReflection->getProperty($stagesProperty)->isPrivate())->toBeTrue();
});

it('collects stages fluently', function () {
  $pipelineBuilder = new PipelineBuilder;
  expect($pipelineBuilder->add(fn ($traveler): int => $traveler * 2))->toBe($pipelineBuilder);
});

it('builds and processes pipelines from stages', function () {
  $pipelineBuilder = (new PipelineBuilder)->add(fn ($traveler): int => $traveler * 2);
  $result = $pipelineBuilder->build()->process(4);
  expect($result)->toBe(8);
});
