<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface IntlControllerInterface
{
  public function l($message, $context) : string;
}
