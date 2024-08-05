<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

/**
 * @template T
 */
interface ProcessorContract
{
  /**
   * @param T $traveler
   * @param callable ...$stages
   * @return T
   */
  public function process($traveler, callable ...$stages);
}
