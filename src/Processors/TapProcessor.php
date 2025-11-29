<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

use InvalidArgumentException;

/**
 * @template T
 * @implements ProcessorContract<T>
 * @deprecated 4.1.0 Use Processor instead with beforeEach() and/or afterEach() methods
 */
class TapProcessor implements ProcessorContract
{
  /**
   * @param callable(T): void|null $beforeCallback
   * @param callable(T): void|null $afterCallback
   * @throws InvalidArgumentException
   */
  public function __construct(
    private mixed $beforeCallback = null,
    private mixed $afterCallback = null,
  ) {
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
    }

    return $traveler;
  }
}
