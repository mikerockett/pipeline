<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

use InvalidArgumentException;

/**
 * Apply callbacks to run before and/or after each stage.
 *
 * @template T
 * @implements ProcessorContract<T>
 */
class TapProcessor implements ProcessorContract
{
  /**
   * @param callable|null $beforeCallback Callback to execute before processing each stage
   * @param callable|null $afterCallback Callback to execute after processing each stage
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

  public function beforeEach(callable $callback): self
  {
    $this->beforeCallback = $callback;

    return $this;
  }

  public function afterEach(callable $callback): self
  {
    $this->afterCallback = $callback;

    return $this;
  }

  /**
   * @param T $traveler
   * @param callable ...$stages
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
