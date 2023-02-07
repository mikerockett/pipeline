<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Builder;

use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Pipeline;
use Rockett\Pipeline\Processors\ProcessorContract;

/** @property callable[] $stages */
class PipelineBuilder implements PipelineBuilderContract
{
  private $stages = [];

  public function add(callable $stage): PipelineBuilderContract
  {
    $this->stages[] = $stage;
    return $this;
  }

  public function build(ProcessorContract $processor = null): PipelineContract
  {
    return new Pipeline($processor, ...$this->stages);
  }
}
