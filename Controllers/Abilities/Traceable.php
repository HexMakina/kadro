<?php

namespace HexMakina\kadro\Controllers\Abilities;

use HexMakina\Tracer\TracerInterface;
use HexMakina\TightORM\Interfaces\ModelInterface;

trait Traceable
{
  abstract public function get_tracer() : TracerInterface;
  abstract public function get_id();
  abstract public static function table_name();


  public function trace()
  {
    ;
  }

  public function traces($options = []) : array
  {
    $options['query_table'] = get_class($this)::table_name();
    $options['query_id'] = $this->get_id();
    return $this->get_tracer()->traces($options);
  }

  // don't really know the purpose of this anymore.. came from Tracer
  public function traces_by_model(ModelInterface $m)
  {
      return $this->get_tracer()->traces(['id' => $m->get_id(), 'table' => get_class($m)::table_name()]);
  }

  public function after_save()
  {
    ;
  }

  public function after_destroy()
  {
    ;
  }
}
