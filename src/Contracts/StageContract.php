<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Contracts;

/**
 * @template T
 * @method bool condition(T $traveler)
 */
interface StageContract
{
  /**
   * @param T $traveler
   */
  public function __invoke($traveler);
}
