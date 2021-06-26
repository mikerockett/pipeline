<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Tests;

use Orchestra\Testbench\TestCase;
use Rockett\Pipeline\Processors\FingersCrossedProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

class FingersCrossedProcessorTest extends TestCase
{
  /** @covers FingersCrossedProcessor::class */
  public function testItImplementsProcessorContract(): void
  {
    $processor = new FingersCrossedProcessor;

    $this->assertTrue($processor instanceof ProcessorContract);
  }

  /** @covers FingersCrossedProcessor::class */
  public function testItProcessesStages(): void
  {
    $result = (new FingersCrossedProcessor)->process(
      1,
      fn ($traveler): int => $traveler * 2
    );

    $this->assertEquals(2, $result);
  }
}
