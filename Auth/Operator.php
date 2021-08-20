<?php

namespace HexMakina\kadro\Auth;

use HexMakina\Crudites\Interfaces\SelectInterface;
use HexMakina\TightORM\{TightModel,RelationManyToMany};

class Operator extends TightModel implements OperatorInterface
{
    const TABLE_NAME = 'kadro_operator';
    const TABLE_ALIAS = 'operator';


    protected $permissions = null;

    // use Permissionability;
    use RelationManyToMany;

    public function __toString()
    {
        return $this->get('username');
    }

    public function is_active(): bool
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
      $this->set('password', password_hash($this->validate_password($string), PASSWORD_DEFAULT));
    }

    public function password_verify($string): bool
    {
      return password_verify($this->validate_password($string), $this->password());
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

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        $Query = static::table()->select();
        if (isset($options['eager']) && $options['eager'] === true) {
            $Query->group_by('id');

            $Query->auto_join([ACL::table(), 'acl'], null, 'LEFT OUTER');
            $Query->auto_join([Permission::table(), 'kadro_permission'], null, 'LEFT OUTER');
            $Query->select_also(["GROUP_CONCAT(DISTINCT kadro_permission.id) as permission_ids", "GROUP_CONCAT(DISTINCT kadro_permission.name) as permission_names"]);
        }

        if (isset($filters['model']) && !empty($filters['model'])) {
            $Query->join([static::otm('t'), static::otm('a')], [[static::otm('a'),static::otm('k'), 't_from','id']], 'INNER');
            $Query->aw_fields_eq(['model_id' => $filters['model']->get_id(), 'model_type' => get_class($filters['model'])::model_type()], static::otm('a'));
        }

        $Query->order_by([$Query->table_label(), 'name', 'ASC']);


        return $Query;
    }

    public function immortal(): bool
    {
        return true; // never delete a user, always deactivate
    }

    public function permission_names()
    {
        if (property_exists($this, 'permission_names') && !is_null($this->get('permission_names'))) {
            return explode(',', $this->get('permission_names'));
        } elseif (property_exists($this, 'permission_ids') && !is_null($this->get('permission_ids'))) {
            $ids = explode(',', $this->get('permission_ids'));
            $ret = [];
            $permissions = Permission::get_many_by_AIPK($ids);
            foreach ($permissions as $id => $p) {
                $ret[] = "$p";
            }
            return $ret;
        } else {
            return ACL::permissions_names_for($this);
        }
    }

    public function permissions()
    {

        if (!is_null($this->permissions)) {
            return $this->permissions;
        }
        $permission_unique_keys = null;
        if (property_exists($this, 'permission_names') && !is_null($this->get('permission_names'))) {
            $permission_unique_keys = explode(',', $this->get('permission_names'));
            $this->permissions = Permission::retrieve(Permission::table()->select()->aw_string_in('name', $permission_unique_keys));
        } elseif (property_exists($this, 'permission_ids') && !is_null($this->get('permission_ids'))) {
            $permission_unique_keys = explode(',', $this->get('permission_ids'));
            $this->permissions = Permission::retrieve(Permission::table()->select()->aw_numeric_in('id', $permission_unique_keys));
        } else {
            $this->permissions = ACL::permissions_for($this);
        }

        return $this->permissions;
    }

    public function has_permission($p): bool
    {
      // new instances or inactive operators, none shall pass
        if ($this->is_new() === true || $this->is_active()  === false) {
            return false;
        }

        $permission_name = $permission_id = null;
        if (is_subclass_of($p, '\HexMakina\kadro\Auth\Permission')) {
            $permission_name = $p->get('name');
            $permission_id = $p->get_id();
        } elseif (preg_match('/[0-9]+/', $p)) {
            $permission_id = $p;
        } else {
            $permission_name = $p;
        }

        if (!is_null($this->get('permission_names')) && !is_null($permission_name)) {
            return strpos($this->get('permission_names'), $permission_name) !== false;
        } elseif (!is_null($this->get('permission_ids')) && !is_null($permission_id)) {
            return strpos($this->get('permission_ids'), $permission_id) !== false;
        } elseif (!is_null($permission_name)) {
            if (method_exists($this, $permission_name) && $this->$permission_name() == true) {
                return true;
            } elseif (property_exists($this, $permission_name) && $this->get('$permission_name') == true) {
                return true;
            } elseif (ACL::match($this, $permission_name) === true) {
                return true;
            }
        }

        return false;
    }

    private function validate_password($string) : string
    {
      if(empty($string))
        throw new \InvalidArgumentException('PASSWORD_CANT_BE_EMPTY');

      return $string;
    }

}
