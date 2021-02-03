<?php

declare(strict_types=1);

namespace Rockett\Pipeline\Processors;

class FingersCrossedProcessor implements ProcessorContract
{
  public function process($traveler, callable ...$stages)
  {
    foreach ($stages as $stage) {
      $traveler = $stage($traveler);
    }

    return $traveler;
  }
}
