<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface ORMController extends CRUDController
{
  public function class_name() : string;

  // returns a ModelInterface on success
  // returns array of errors [field => message] on failure
  public function persist_model($model);
}


