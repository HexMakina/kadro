<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\ORM\ModelInterface;
use HexMakina\Crudites\Queries\AutoJoin;

trait HasOperator
{
    use \HexMakina\TightORM\HasOne;

    private $operator = null;

    abstract public function get($prop_name);

    public function operator(OperatorInterface $setter = null)
    {
        if (!is_null($setter)) {
            $this->operator = $setter;
        }

        if (is_null($this->operator)) {
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

        if (is_null($this->operator) && !empty($this->get('operator_id'))) {
            $this->operator = Operator::exists($this->get('operator_id'));
        }

        return $this->operator;
    }


    public static function enhance_query_retrieve($Query, $filters, $options)
    {
        AutoJoin::join($Query,[ACL::table(),'ACL'], null, 'LEFT OUTER');
        $permission_alias = AutoJoin::join($Query,[Permission::table(), 'permission'], null, 'LEFT OUTER');

        $permission_ids_and_names = [];
        $permission_ids_and_names [] = sprintf('GROUP_CONCAT(DISTINCT %s.%s) as %s', $permission_alias, 'id', $permission_alias . '_ids');
        $permission_ids_and_names [] = sprintf('GROUP_CONCAT(DISTINCT %s.%s) as %s', $permission_alias, 'name', $permission_alias . '_names');
        $Query->selectAlso($permission_ids_and_names);

        $Query->selectAlso(['operator.name as operator_name', 'operator.active as operator_active']);

        if (isset($filters['username'])) {
            $Query->whereEQ('username', $filters['username'], 'operator');
        }

        if (isset($filters['email'])) {
            $Query->whereEQ('email', $filters['email'], 'operator');
        }

        if (isset($filters['active'])) {
            $Query->whereEQ('active', $filters['active'], 'operator');
        }

        return $Query;
    }
}
