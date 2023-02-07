<?php

declare(strict_types=1);

namespace Rockett\Pipeline;

use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Processors\{FingersCrossedProcessor, ProcessorContract};

/** @property callable[] $stages */
class Pipeline implements PipelineContract
{
  private ProcessorContract $processor;
  private array $stages;

  public function __construct(
    ProcessorContract $processor = null,
    callable ...$stages
  ) {
    $this->processor = $processor ?? new FingersCrossedProcessor;
    $this->stages = $stages;
  }

  public function pipe(callable $stage): PipelineContract
  {
    $pipeline = clone $this;
    $pipeline->stages[] = $stage;

    return $pipeline;
  }

  public function process($traveler)
  {
    return $this->processor->process($traveler, ...$this->stages);
  }

  public function __invoke($traveler)
  {
    return $this->process($traveler);
  }
}
