<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

/**
 * @template T
 * @implements ProcessorContract<T>
 */
class FingersCrossedProcessor implements ProcessorContract
{
  /**
   * @param T $traveler
   * @param callable ...$stages
   * @return T
   */
  public function process($traveler, callable ...$stages)
  {
    foreach ($stages as $stage) {
      $traveler = $stage($traveler);
    }

    return $traveler;
  }
}
