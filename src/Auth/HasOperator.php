<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\ORM\ModelInterface;
use HexMakina\Crudites\Queries\AutoJoin;

trait HasOperator
{
    use \HexMakina\TightORM\HasOne;

    private $operator;

    abstract public function get($prop_name);

    public function operator(OperatorInterface $operator = null)
    {
        if (!is_null($operator)) {
            $this->operator = $operator;
        }

        if (is_null($this->operator)) {
            $model = self::extract(new Operator(), $this, true);
            if (!is_null($model)) {
                foreach (['permission_names', 'permission_ids'] as $permission_marker) {
                    if (property_exists($this, $permission_marker)) {
                        $model->set($permission_marker, $this->$permission_marker);
                    }
                }

                $this->operator = $model;
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
        $Query->selectAlso([
            $permission_alias . '_ids' => [sprintf('GROUP_CONCAT(DISTINCT %s.%s)', $permission_alias, 'id')],
            $permission_alias . '_names' => [sprintf('GROUP_CONCAT(DISTINCT %s.%s)', $permission_alias, 'name')],
            'operator_name' => ['operator', 'name'],
            'operator_active' => ['operator', 'active']
        ]);


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
