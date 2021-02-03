<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Builder;

use Rockett\Pipeline\Contracts\PipelineContract;
use Rockett\Pipeline\Processors\ProcessorContract;

interface PipelineBuilderContract
{
  public function add(callable $stage): PipelineBuilderContract;
  public function build(ProcessorContract $processor = null): PipelineContract;
}
