<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Contracts;

interface StageContract
{
  public function __invoke($traveler);
}
