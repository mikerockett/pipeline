<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Contracts;

/**
 * @template T
 * @implements StageContract<T>
 */
interface PipelineContract extends StageContract
{
  /**
   * @return PipelineContract<T>
   */
  public function pipe(callable $operation): PipelineContract;

  /**
   * @param T $traveler
   */
  public function process($traveler);
}
