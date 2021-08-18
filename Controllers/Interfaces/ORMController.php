<?php

namespace HexMakina\kadro\Controllers\Interfaces;

use \HexMakina\TightORM\Interfaces\ModelInterface;

interface ORMController extends CRUDControllerInterface
{
  public function class_name() : string;

  // returns a ModelInterface on success
  // returns array of errors [field => message] on failure
  public function persist_model($model) : ?ModelInterface;
}
