<?php

namespace HexMakina\kadro\Models;

use \HexMakina\Crudites\Queries\Select;
use \HexMakina\Crudites\TightModel;

class Traduko extends TightModel
{
  const TABLE_NAME = 'kadro_traduki';
  const TABLE_ALIAS = 'traduko';

	public function traceable() : bool
	{
		return false;
	}

  public function immortal() : bool
  {
    return false;
  }

  public static function query_retrieve($filters=[], $options=[]) : Select
  {
    $Query = static::table()->select();
    $Query->order_by(['kategorio', 'ASC']);
    $Query->order_by(['sekcio', 'ASC']);
    $Query->order_by(['referenco', 'ASC']);

    return $Query;
  }
}


