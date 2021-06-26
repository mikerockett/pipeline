<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Tests;

use Orchestra\Testbench\TestCase;
use Rockett\Pipeline\Processors\TapProcessor;
use Rockett\Pipeline\Processors\ProcessorContract;

class TapProcessorTest extends TestCase
{
  static $beforeLogs = [];
  static $afterLogs = [];
  static $fingersCrossedLogs = [];

  /** @covers TapProcessor::class */
  public function testItImplementsProcessorContract(): void
  {
    $processor = new TapProcessor();

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

  /** @covers Pipeline::class */
  public function testItHandlesBeforeCallbacks(): void
  {
    (new TapProcessor(fn ($traveler) => static::$beforeLogs[] = $traveler))
      ->process(1, ...static::stages());

    $this->assertEquals([1, 3, 30], static::$beforeLogs);
  }

  /** @covers Pipeline::class */
  public function testItHandlesAfterCallbacks(): void
  {
    (new TapProcessor(null, fn ($traveler) => static::$afterLogs[] = $traveler))
      ->process(1, ...static::stages());

    $this->assertEquals([3, 30, 300], static::$afterLogs);
  }
}
