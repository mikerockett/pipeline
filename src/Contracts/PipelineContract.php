<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Contracts;

interface PipelineContract extends StageContract
{
  public function pipe(callable $operation): PipelineContract;
  public function process($traveler);
}
