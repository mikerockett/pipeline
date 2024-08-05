<?php

declare(strict_types=1);

namespace Rockett\Pipeline;

use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Processors\{FingersCrossedProcessor, ProcessorContract};

/**
 * @template T
 * @implements PipelineContract<T>
 */
class Pipeline implements PipelineContract
{
  private ProcessorContract $processor;

  /** @var callable[] */
  private array $stages;

  public function __construct(
    ?ProcessorContract $processor = null,
    callable ...$stages
  ) {
    $this->processor = $processor ?? new FingersCrossedProcessor();
    $this->stages = $stages;
  }

  public function pipe(callable $stage): self
  {
    $pipeline = clone $this;
    $pipeline->stages[] = $stage;

    return $pipeline;
  }

  /**
   * @param T $traveler
   * @return T
   */
  public function process($traveler): mixed
  {
    return $this->processor->process($traveler, ...$this->stages);
  }

  /**
   * @param T $traveler
   * @return T
   */
  public function __invoke($traveler): mixed
  {
    return $this->process($traveler);
  }
}
