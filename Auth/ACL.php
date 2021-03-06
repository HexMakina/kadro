<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\Crudites\Queries\AutoJoin;

class ACL extends \HexMakina\TightORM\TightModel
{
    public const TABLE_NAME = 'kadro_acl';
    public const TABLE_ALIAS = 'acl';

    public function traceable(): bool
    {
        return false;
    }

    public static function match(OperatorInterface $op, $permission_name)
    {
        return in_array($permission_name, self::permissions_names_for($op));
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        $options['eager'] = false;
        $ret = parent::query_retrieve($filters, $options);
        $eager_params = [];
        $eager_params[Permission::relationalMappingName()] = Permission::tableAlias();
        $eager_params[Operator::relationalMappingName()] = Operator::tableAlias();
        $eager_params[ACL::relationalMappingName()] = ACL::tableAlias();

        // why ? why dont you comment.. is the real question
        AutoJoin::eager($ret, $eager_params);

        return $ret;
    }

    public static function permissions_for(OperatorInterface $op)
    {
        $res = self::any(['operator_id' => $op->getId()]);

        $permission_ids = [];
        foreach ($res as $r) {
            $permission_ids[] = $r->get('permission_id');
        }

        $ret = Permission::filter(['ids' => $permission_ids]);
        return $ret;
    }

    public static function permissions_names_for(OperatorInterface $op)
    {
        $operator_with_perms = get_class($op)::exists($op->getId());
        // $operator_with_perms = get_class($op)::retrieve($operator_with_perms);
        if (is_null($operator_with_perms)) {
            return [];
        }

        return explode(',', $operator_with_perms->get('permission_names'));
    }

    public static function allow_in(OperatorInterface $op, Permission $p)
    {
        $ret = new ACL();
        $ret->set('operator_id', $op->getId());
        $ret->set('permission_id', $p->getId());
        $ret->save($op->getId());
        return $ret;
    }
}
