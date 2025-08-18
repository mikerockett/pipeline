<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

use InvalidArgumentException;

/**
 * Based on the result of a callback, this processor allows the pipeline
 * to be interrupted (equivalent of returning early).
 *
 * @template T
 * @implements ProcessorContract<T>
 */
class InterruptibleProcessor implements ProcessorContract
{
  private bool $inverseCallbackOutcome = false;

  /**
   * @param callable|null $beforeCallback Callback that may interrupt processing
   */
  public function __construct(private mixed $callback)
  {
    if (!is_callable($callback)) {
      throw new InvalidArgumentException('$callback must be callable');
    }
  }

  public static function continueUnless(callable $callback): self
  {
    return new static($callback);
  }

  public static function continueWhen(callable $callback): self
  {
    return (new static($callback))->withInversedConditioner();
  }

  public function withInversedConditioner(): self
  {
    $this->inverseCallbackOutcome = true;

    return $this;
  }

  /**
   * @param T $traveler
   * @param callable ...$stages
   * @return T
   */
  public function process($traveler, callable ...$stages)
  {
    foreach ($stages as $stage) {
      $traveler = $stage($traveler);
      $callbackOutcome = ($this->callback)($traveler);

      $outcomeIsTruthy = match ($this->inverseCallbackOutcome) {
        true => !$callbackOutcome,
        false => !!$callbackOutcome,
      };

      if ($outcomeIsTruthy) {
        return $traveler;
      }
    }

    return $traveler;
  }
}
