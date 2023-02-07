<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

class InterruptibleProcessor implements ProcessorContract
{
  private $callback;
  private bool $inverseCallbackOutcome = false;

  public function __construct(callable $callback)
  {
    $this->callback = $callback;
  }

  public static function continueUnless(callable $callable): self
  {
    return new static($callable);
  }

  public static function continueWhen(callable $callable): self
  {
    return (new static($callable))->withInversedConditioner();
  }

  public function withInversedConditioner(): self
  {
    $this->inverseCallbackOutcome = true;
    return $this;
  }

  public function process($traveler, callable ...$stages)
  {
    $callback = $this->callback;

    foreach ($stages as $stage) {
      $traveler = $stage($traveler);
      $callbackOutcome = $callback($traveler);

      $outcomeIsTruthy = $this->inverseCallbackOutcome
        ? !$callbackOutcome
        : !!$callbackOutcome;

      if ($outcomeIsTruthy) {
        return $traveler;
      }
    }

    return $traveler;
  }
}
