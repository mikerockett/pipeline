<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Tests;

use Orchestra\Testbench\TestCase;
use Rockett\Pipeline\Processors\InterruptibleProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

class InterruptibleProcessorTest extends TestCase
{
  /** @covers InterruptibleProcessor::class */
  public function testItImplementsProcessorContract(): void
  {
    $processor = new InterruptibleProcessor(fn (): bool => false);

    $this->assertTrue($processor instanceof ProcessorContract);
  }

  private static function stages(): array
  {
    return [
      fn ($traveler): int => $traveler + 2,
      fn ($traveler): int => $traveler * 10,
      fn ($traveler): int => $traveler * 10
    ];
  }

  /** @covers InterruptibleProcessor::class */
  public function testItInterruptsProcessing(): void
  {
    $result = (new InterruptibleProcessor(fn ($traveler): bool => $traveler > 10))
      ->process(5, ...static::stages());

    $this->assertEquals(70, $result);
  }

  /** @covers InterruptibleProcessor::class */
  public function testItInterruptsProcessingUsingContinueWhen(): void
  {
    $result = InterruptibleProcessor::continueWhen(fn ($traveler): bool => $traveler < 10)
      ->process(5, ...static::stages());

    $this->assertEquals(70, $result);
  }

  /** @covers InterruptibleProcessor::class */
  public function testItInterruptsProcessingUsingContinueUnless(): void
  {
    $result = InterruptibleProcessor::continueUnless(fn ($traveler): bool => $traveler > 10)
      ->process(5, ...static::stages());

    $this->assertEquals(70, $result);
  }
}
