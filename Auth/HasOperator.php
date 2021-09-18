<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\ORM\ModelInterface;

trait HasOperator
{
  use \HexMakina\TightORM\HasOne;

  private $operator = null;

  abstract public function get($prop_name);

  public function operator(OperatorInterface $setter=null)
  {
    if(!is_null($setter))
      $this->operator = $setter;

    if(is_null($this->operator))
    {
      $extract_attempt = self::extract(new Operator(), $this, true);
      if (!is_null($extract_attempt)) {
          foreach (['permission_names', 'permission_ids'] as $permission_marker) {
              if (property_exists($this, $permission_marker)) {
                  $extract_attempt->set($permission_marker, $this->$permission_marker);
              }
          }

          $this->operator = $extract_attempt;
      }
    }

    if(is_null($this->operator) && !empty($this->get('operator_id')))
      $this->operator = Operator::exists($this->get('operator_id'));

    return $this->operator;
  }
}
