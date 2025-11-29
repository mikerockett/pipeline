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
  /** @var array<callable(T): T> */
  private array $stages;

  /**
   * @param callable(T): T ...$stages
   */
  public function __construct(
    private ProcessorContract|null $processor = null,
    callable ...$stages
  ) {
    $this->processor = $processor ?? new FingersCrossedProcessor();
    $this->stages = $stages;
  }

  /**
   * @param callable(T): T $stage
   */
  public function pipe(callable $stage): self
  {
    $pipeline = clone $this;
    $pipeline->stages[] = $stage;

    return $pipeline;
  }

  public function withProcessor(ProcessorContract $processor): self
  {
    $pipeline = clone $this;
    $pipeline->processor = $processor;

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
