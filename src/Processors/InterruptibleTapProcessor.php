<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

use InvalidArgumentException;

/**
 * Combines the InterruptibleProcess and TapProcessor.
 *
 * @template T
 * @implements ProcessorContract<T>
 * @deprecated 4.1.0 Use Processor instead with continueUnless()/continueWhen() and beforeEach()/afterEach() methods
 */
class InterruptibleTapProcessor implements ProcessorContract
{
  private bool $inverseCallbackOutcome = false;

  /**
   * @param callable(T): bool $interruptCallback
   * @param callable(T): void|null $beforeCallback
   * @param callable(T): void|null $afterCallback
   */
  public function __construct(
    private mixed $interruptCallback,
    private mixed $beforeCallback = null,
    private mixed $afterCallback = null,
  ) {
    if (!is_callable($interruptCallback)) {
      throw new InvalidArgumentException('$callback must be callable');
    }

    if ($beforeCallback === null && $afterCallback === null) {
      throw new InvalidArgumentException(
        'At least one of $beforeCallback and $afterCallback must be provided'
      );
    }

    if ($beforeCallback && !is_callable($beforeCallback)) {
      throw new InvalidArgumentException('$beforeCallback must be callable');
    }

    if ($afterCallback && !is_callable($afterCallback)) {
      throw new InvalidArgumentException('$afterCallback must be callable');
    }
  }

  /**
   * @param callable(T): bool $callback
   * @param callable(T): void|null $beforeCallback
   * @param callable(T): void|null $afterCallback
   */
  public static function continueUnless(
    callable $callback,
    callable|null $beforeCallback = null,
    callable|null $afterCallback = null
  ): self {
    return new static(
      $callback,
      $beforeCallback,
      $afterCallback
    );
  }

  /**
   * @param callable(T): bool $callback
   * @param callable(T): void|null $beforeCallback
   * @param callable(T): void|null $afterCallback
   */
  public static function continueWhen(
    callable $callback,
    callable|null $beforeCallback = null,
    callable|null $afterCallback = null
  ): self {
    return (new static(
      $callback,
      $beforeCallback,
      $afterCallback
    ))->withInversedConditioner();
  }

  /**
   * @param callable(T): void $callback
   */
  public function beforeEach(callable $callback): self
  {
    $this->beforeCallback = $callback;

    return $this;
  }

  /**
   * @param callable(T): void $callback
   */
  public function afterEach(callable $callback): self
  {
    $this->afterCallback = $callback;

    return $this;
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
    [$before, $after] = [
      $this->beforeCallback,
      $this->afterCallback,
    ];

    foreach ($stages as $stage) {
      if ($before) {
        $before($traveler);
      }

      $traveler = $stage($traveler);

      if ($after) {
        $after($traveler);
      }

      $callbackOutcome = ($this->interruptCallback)($traveler);

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
