<?php

namespace HexMakina\kadro\Auth;
use \HexMakina\Crudites\Queries\BaseQuery;

class ACL extends \HexMakina\ORM\TightModel
{
  const TABLE_NAME = 'kadro_acl';
  const TABLE_ALIAS = 'acl';

  public function traceable() : bool
	{
		return false;
	}

  public static function match(OperatorInterface $op, $permission_name)
  {
    return in_array($permission_name, self::permissions_names_for($op));
  }

  public static function query_retrieve($filters=[], $options=[]) : BaseQuery
  {
    $options['eager'] = false;
    $ret = parent::query_retrieve($filters,$options);
    $eager_params = [];
    $eager_params[Permission::table_name()]=Permission::table_alias();
    $eager_params[Operator::table_name()]=Operator::table_alias();
    $eager_params[ACL::table_name()]=ACL::table_alias();

    $ret->eager($eager_params);

    return $ret;
  }

  public static function permissions_for(OperatorInterface $op)
  {
    $res = self::any(['operator_id'=>$op->get_id()]);

    $permission_ids = [];
    foreach($res as $r)
      $permission_ids[]=$r->get('permission_id');

    $ret = Permission::filter(['ids'=>$permission_ids]);
    return $ret;
  }
  public static function permissions_names_for(OperatorInterface $op)
  {
    $operator_with_perms = get_class($op)::exists($op->operator_id());
    // $operator_with_perms = get_class($op)::retrieve($operator_with_perms);
    if(is_null($operator_with_perms))
      return [];

    return explode(',',$operator_with_perms->get('permission_names'));
  }

  public static function allow_in(OperatorInterface $op, Permission $p)
  {
    $ret = new ACL();
    $ret->set('operator_id', $op->get_id());
    $ret->set('permission_id', $p->get_id());
    $ret->save($op->get_id());
    return $ret;
  }

}

?>
