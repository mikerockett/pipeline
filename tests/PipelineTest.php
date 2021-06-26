<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Tests;

use Orchestra\Testbench\TestCase;
use ReflectionClass;
use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Contracts\StageContract;
use Rockett\Pipeline\Pipeline;

class PipelineTest extends TestCase
{
  /** @covers Pipeline::class */
  public function testItImplementsPipelineContract(): void
  {
    $pipeline = new Pipeline;

    $this->assertTrue($pipeline instanceof PipelineContract);
  }

  /** @covers Pipeline::class */
  public function testItInstantiatesWithPrivateProperties(): void
  {
    $pipeline = new Pipeline;
    $instanceReflection = new ReflectionClass($pipeline);

    $this->assertTrue($instanceReflection->hasProperty($processorProperty = 'processor'));
    $this->assertTrue($instanceReflection->getProperty($processorProperty)->isPrivate());
    $this->assertTrue($instanceReflection->hasProperty($stagesProperty = 'stages'));
    $this->assertTrue($instanceReflection->getProperty($stagesProperty)->isPrivate());
  }

  /** @covers Pipeline::class */
  public function testItIsImmutableWhenPiping(): void
  {
    $pipeline = new Pipeline;
    $withPipe = $pipeline->pipe(fn (): int => 1);

    $this->assertNotEquals($pipeline, $withPipe);
  }

  /** @covers Pipeline::class */
  public function testItPipesOperations(): void
  {
    $result = (new Pipeline)
      ->pipe(fn (): int => 1)
      ->process('1');

    $this->assertEquals(1, $result);
  }

  /** @covers Pipeline::class */
  public function testItProcessesPayloads(): void
  {
    $result = (new Pipeline)
      ->pipe(fn ($traveler): int => $traveler + 1)
      ->process(1);

    $this->assertEquals(2, $result);
  }

  /** @covers Pipeline::class */
  public function testItProcessesPayloadsInSequence(): void
  {
    $result = (new Pipeline)
      ->pipe(fn ($traveler): int => $traveler + 2)
      ->pipe(fn ($traveler): int => $traveler * 10)
      ->process(1);

    $this->assertEquals(30, $result);
  }

  /** @covers Pipeline::class */
  public function testItProcessesPayloadsUsingStageContractImplementations(): void
  {
    $result = (new Pipeline)
      ->pipe(
        new class implements StageContract
        {
          public function __invoke($traveler)
          {
            return strrev($traveler);
          }
        }
      )
      ->process('payload');

    $this->assertEquals(strrev('payload'), $result);
  }
}
