<?php

namespace HexMakina\kadro\Auth;

use \HexMakina\Crudites\Interfaces\SelectInterface;
use \HexMakina\Crudites\{TightModel,RelationManyToMany};

class Operator extends TightModel implements OperatorInterface
{
  const TABLE_NAME = 'kadro_operator';
  const TABLE_ALIAS = 'operator';

  use Permissionability;
  use RelationManyToMany;

  public function __toString()
  {
    return $this->get('username');
  }

  public function is_active() : bool
  {
    return !empty($this->get('active'));
  }

  public function operator_id()
  {
    return $this->get_id();
  }

  public function username()
  {
    return $this->get('username');
  }

  public function password()
  {
    return $this->get('password');
  }

  public function password_change($string)
  {
    $this->set('password', password_hash($string, PASSWORD_DEFAULT));
  }

  public function password_verify($string) : bool
  {
    return password_verify($string, $this->password());
  }

  public function name()
  {
    return $this->get('name');
  }

  public function email()
  {
    return $this->get('email');
  }
  public function phone()
  {
    return $this->get('phone');
  }

  public function language_code()
  {
    return $this->get('language_code');
  }

  public static function query_retrieve($filters=[], $options=[]) : SelectInterface
  {
    $Query = static::table()->select();
    if(isset($options['eager']) && $options['eager'] === true)
    {
      $Query->group_by('id');

      $Query->auto_join([ACL::table(), 'acl'], null, 'LEFT OUTER');
      $Query->auto_join([Permission::table(), 'kadro_permission'], null, 'LEFT OUTER');
      $Query->select_also(["GROUP_CONCAT(DISTINCT kadro_permission.id) as permission_ids", "GROUP_CONCAT(DISTINCT kadro_permission.name) as permission_names"]);
    }

    if(isset($filters['model']) && !empty($filters['model']))
    {
      $Query->join([static::otm('t'), static::otm('a')],[[static::otm('a'),static::otm('k'), 't_from','id']], 'INNER');
      $Query->aw_fields_eq(['model_id' => $filters['model']->get_id(), 'model_type' => get_class($filters['model'])::model_type()], static::otm('a'));
    }

    $Query->order_by([$Query->table_label(), 'name', 'ASC']);


    return $Query;
  }

  public function immortal() : bool
  {
    return true; // never delete a user, always deactivate
  }
}
