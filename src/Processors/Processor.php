<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

/**
 * @template T
 * @implements ProcessorContract<T>
 */
class Processor implements ProcessorContract
{
  protected mixed $interrupt = null;
  protected bool $invertCondition = false;
  protected mixed $before = null;
  protected mixed $after = null;

  /**
   * @param callable(T): bool $callback
   */
  public function continueUnless(callable $callback): self
  {
    $this->interrupt = $callback;
    $this->invertCondition = false;

    return $this;
  }

  /**
   * @param callable(T): bool $callback
   */
  public function continueWhen(callable $callback): self
  {
    $this->interrupt = $callback;
    $this->invertCondition = true;

    return $this;
  }

  public function invert(): self
  {
    $this->invertCondition = !$this->invertCondition;

    return $this;
  }

  /**
   * @param callable(T): void $callback
   */
  public function beforeEach(callable $callback): self
  {
    $this->before = $callback;

    return $this;
  }

  /**
   * @param callable(T): void $callback
   */
  public function afterEach(callable $callback): self
  {
    $this->after = $callback;

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
      if ($this->before) {
        ($this->before)($traveler);
      }

      if (method_exists($stage, 'condition') && !$stage->condition($traveler)) {
        continue;
      }

      $traveler = $stage($traveler);

      if ($this->after) {
        ($this->after)($traveler);
      }

      if ($this->interrupt) {
        $callbackOutcome = ($this->interrupt)($traveler);

        if ((bool) $callbackOutcome ^ $this->invertCondition) {
          return $traveler;
        }
      }
    }

    return $traveler;
  }
}
