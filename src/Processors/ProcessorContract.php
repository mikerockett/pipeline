<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

interface ProcessorContract
{
  public function process($traveler, callable ...$stages);
}
