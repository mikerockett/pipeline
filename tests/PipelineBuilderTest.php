<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Tests;

use Orchestra\Testbench\TestCase;
use ReflectionClass;
use Rockett\Pipeline\Builder\PipelineBuilder;
use Rockett\Pipeline\Builder\PipelineBuilderContract;

class PipelineBuilderTest extends TestCase
{
  /** @covers PipelineBuilder::class */
  public function testItImplementsPipelineBuilderContract(): void
  {
    $pipelineBuilder = new PipelineBuilder;

    $this->assertTrue($pipelineBuilder instanceof PipelineBuilderContract);
  }

  /** @covers PipelineBuilder::class */
  public function testItInstantiatesWithPrivateProperties(): void
  {
    $pipelineBuilder = new PipelineBuilder;
    $instanceReflection = new ReflectionClass($pipelineBuilder);

    $this->assertTrue($instanceReflection->hasProperty($stagesProperty = 'stages'));
    $this->assertTrue($instanceReflection->getProperty($stagesProperty)->isPrivate());
  }

  /** @covers PipelineBuilder::class */
  public function testItCollectsStagesFluently(): void
  {
    $pipelineBuilder = new PipelineBuilder;

    $this->assertTrue($pipelineBuilder->add(fn (): int => 2) === $pipelineBuilder);
  }

  /** @covers PipelineBuilder::class */
  public function testItBuildsAndProcessesPipelinesFromStages(): void
  {
    $pipelineBuilder = (new PipelineBuilder)->add(fn ($traveler): int => $traveler * 2);
    $result = $pipelineBuilder->build()->process(4);

    $this->assertEquals(8, $result);
  }
}
