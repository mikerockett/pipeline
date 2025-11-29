<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

use InvalidArgumentException;

/**
 * @template T
 * @implements ProcessorContract<T>
 * @deprecated 4.1.0 Use Processor instead with continueUnless() or continueWhen() methods
 */
class InterruptibleProcessor implements ProcessorContract
{
  private bool $inverseCallbackOutcome = false;

  /**
   * @param callable(T): bool $callback
   */
  public function __construct(private mixed $callback)
  {
    if (!is_callable($callback)) {
      throw new InvalidArgumentException('$callback must be callable');
    }
  }

  /**
   * @param callable(T): bool $callback
   */
  public static function continueUnless(callable $callback): self
  {
    return new static($callback);
  }

  /**
   * @param callable(T): bool $callback
   */
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
   * @param callable(T): T ...$stages
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
