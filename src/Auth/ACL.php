<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\Crudites\Queries\AutoJoin;

class ACL extends \HexMakina\TightORM\TightModel
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'kadro_acl';

    /**
     * @var string
     */
    public const TABLE_ALIAS = 'acl';

    public function traceable(): bool
    {
        return false;
    }

    public static function match(OperatorInterface $operator, $permission_name): bool
    {
        return in_array($permission_name, self::permissions_names_for($operator));
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        $options['eager'] = false;
        $select = parent::query_retrieve($filters, $options);
        $eager_params = [];
        $eager_params[Permission::relationalMappingName()] = Permission::tableAlias();
        $eager_params[Operator::relationalMappingName()] = Operator::tableAlias();
        $eager_params[ACL::relationalMappingName()] = ACL::tableAlias();

        // why ? why dont you comment.. is the real question
        AutoJoin::eager($select, $eager_params);

        return $select;
    }

    /**
     * @return mixed[]
     */
    public static function permissions_for(OperatorInterface $operator): array
    {
        $res = self::any(['operator_id' => $operator->getId()]);

        $permission_ids = [];
        foreach ($res as $re) {
            $permission_ids[] = $re->get('permission_id');
        }
        return Permission::filter(['ids' => $permission_ids]);
    }

    public static function permissions_names_for(OperatorInterface $operator)
    {
        $operator_with_perms = get_class($operator)::exists($operator->getId());
        // $operator_with_perms = get_class($op)::retrieve($operator_with_perms);
        if (is_null($operator_with_perms)) {
            return [];
        }

        return explode(',', $operator_with_perms->get('permission_names'));
    }

    public static function allow_in(OperatorInterface $operator, Permission $permission): \HexMakina\kadro\Auth\ACL
    {
        $acl = new ACL();
        $acl->set('operator_id', $operator->getId());
        $acl->set('permission_id', $permission->getId());
        $acl->save($operator->getId());
        return $acl;
    }
}
