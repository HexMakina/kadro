<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\BlackBox\ORM\ModelInterface;

trait Operatorability
{
    private $operator = null;

  // auto build an operator (once) then returns it
  // throws Exception if unable to build due to missing required property
    abstract public function get($prop_name);
    abstract public function extract(ModelInterface $extract_model, $ignore_nullable = false);

    public function set_operator(OperatorInterface $setter)
    {
        $this->operator = $setter;
    }

    public function load_operator($id = null)
    {
        if (!is_null($operator_id = $id ?? $this->get('operator_id'))) { // extraction failed but we have an fk
            $this->operator = Operator::exists($operator_id);
        }
    }

    public function operator(): ?OperatorInterface
    {
        if (is_null($this->operator)) {
            $extract_attempt = $this->extract(new Operator(), true);
            if (!is_null($extract_attempt)) {
                foreach (['permission_names', 'permission_ids'] as $permission_marker) {
                    if (property_exists($this, $permission_marker)) {
                        $extract_attempt->set($permission_marker, $this->$permission_marker);
                    }
                }

                $this->operator = $extract_attempt;
            }
          // elseif(!is_null($this->get('operator_id'))) // extraction failed but we have an fk
          // {
          //   $this->operator = Operator::exists($this->get('operator_id'));
          // }
        }

        return $this->operator;
    }

    public static function enhance_query_retrieve($Query, $filters, $options)
    {
        $Query->auto_join([ACL::table(),'ACL'], null, 'LEFT OUTER');
        $permission_alias = $Query->auto_join([Permission::table(), 'permission'], null, 'LEFT OUTER');

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

    public function isActive(): bool
    {
        return is_null($this->operator()) ? false : $this->operator()->isActive();
    }

    public function operatorId()
    {
        return is_null($this->operator()) ? null : $this->operator()->operatorId();
    }

    public function username()
    {
        return is_null($this->operator()) ? null : $this->operator()->username();
    }

    public function password()
    {
        return is_null($this->operator()) ? null : $this->operator()->password();
    }

    public function passwordChange($string)
    {
        $this->operator()->passwordChange($string);
    }

    public function passwordVerify($string): bool
    {
        return $this->operator()->passwordVerify($string);
    }

    public function name()
    {
        return is_null($this->operator()) ? null : $this->operator()->name();
    }

    public function email()
    {
        return is_null($this->operator()) ? null : $this->operator()->email();
    }

    public function phone()
    {
        return is_null($this->operator()) ? null : $this->operator()->phone();
    }

    public function languageCode()
    {
        return is_null($this->operator()) ? null : $this->operator()->languageCode();
    }

    public function hasPermission($p): bool
    {
        return is_null($this->operator()) ? false : $this->operator()->hasPermission($p);
    }
}
