<?php

namespace HexMakina\kadro\Auth;

class Permission extends \HexMakina\Crudites\TightModel
{
  const TABLE_NAME = 'kadro_permission';
  const TABLE_ALIAS = 'permission';

  public function __toString()
  {
    return $this->get('name');
  }
}
