<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

/**
 * @template T
 * @implements ProcessorContract<T>
 */
class TapProcessor implements ProcessorContract
{
  private $beforeCallback;
  private $afterCallback;

  public function __construct(
    callable $beforeCallback = null,
    callable $afterCallback = null
  ) {
    $this->beforeCallback = $beforeCallback;
    $this->afterCallback = $afterCallback;
  }

  public function beforeEach(callable $callback): self
  {
    $this->beforeCallback = $callback;

    return $this;
  }

  public function afterEach(callable $callback): self
  {
    $this->beforeCallback = $callback;

    return $this;
  }

  /**
   * @param T $traveler
   * @param callable ...$stages
   * @return T
   */
  public function process($traveler, callable ...$stages)
  {
    $beforeCallback = $this->beforeCallback;
    $afterCallback = $this->afterCallback;

    foreach ($stages as $stage) {
      if ($beforeCallback) {
        $beforeCallback($traveler);
      }

      $traveler = $stage($traveler);

      if ($afterCallback) {
        $afterCallback($traveler);
      }
    }

    return $traveler;
  }
}
