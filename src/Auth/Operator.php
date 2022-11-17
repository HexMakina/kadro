<?php

namespace HexMakina\kadro\Auth;

use HexMakina\BlackBox\Database\SelectInterface;
use HexMakina\BlackBox\Auth\OperatorInterface;
use HexMakina\Crudites\Queries\AutoJoin;

use HexMakina\TightORM\{TightModel,RelationManyToMany};

class Operator extends TightModel implements OperatorInterface
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'kadro_operator';

    /**
     * @var string
     */
    public const TABLE_ALIAS = 'operator';


    protected $permissions;

    // use Permissionability;
    use \HexMakina\TightORM\RelationManyToMany;

    public function __toString()
    {
        return $this->get('username');
    }

    public function isActive(): bool
    {
        return !empty($this->get('active'));
    }

    public function operatorId()
    {
        return $this->getId();
    }

    public function username()
    {
        return $this->get('username');
    }

    // TODO remove this, pwd only useful when checkinin
    public function password()
    {
        return $this->get('password');
    }

    public function passwordChange($string): void
    {
        $this->set('password', password_hash($this->validate_password($string), PASSWORD_DEFAULT));
    }

    public function passwordVerify($string): bool
    {
        return password_verify($this->validate_password($string), $this->password() ?? '');
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

    public function languageCode()
    {
        return $this->get('language_code');
    }

    public static function safeLoading($op_id): OperatorInterface
    {
        $op = static::one($op_id);
        $op->set('password', null);
        return $op;
    }

    public static function query_retrieve($filters = [], $options = []): SelectInterface
    {
        $select = static::table()->select();
        if (isset($options['eager']) && $options['eager'] === true) {
            $select->groupBy('id');

            AutoJoin::join($select, [ACL::table(), 'acl'], null, 'LEFT OUTER');
            AutoJoin::join($select, [Permission::table(), 'kadro_permission'], null, 'LEFT OUTER');
            $select->selectAlso(["GROUP_CONCAT(DISTINCT kadro_permission.id) as permission_ids", "GROUP_CONCAT(DISTINCT kadro_permission.name) as permission_names"]);
        }

        if (isset($filters['model']) && !empty($filters['model'])) {
            $select->join([static::otm('t'), static::otm('a')], [[static::otm('a'),static::otm('k'), 't_from','id']], 'INNER');
            $select->whereFieldsEQ(['model_id' => $filters['model']->getId(), 'model_type' => get_class($filters['model'])::model_type()], static::otm('a'));
        }

        $select->orderBy([$select->tableLabel(), 'name', 'ASC']);


        return $select;
    }

    public function immortal(): bool
    {
        return true; // never delete a user, always deactivate
    }

    public function permission_names()
    {
        if (property_exists($this, 'permission_names') && !is_null($this->get('permission_names'))) {
            return explode(',', $this->get('permission_names') ?? '');
        }
        if (property_exists($this, 'permission_ids') && !is_null($this->get('permission_ids'))) {
            $ids = explode(',', $this->get('permission_ids') ?? '');
            $ret = [];
            $permissions = Permission::get_many_by_AIPK($ids);
            foreach ($permissions as $permission) {
                $ret[] = sprintf('%s', $permission);
            }

            return $ret;
        }
        else {
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
            $permission_unique_keys = explode(',', $this->get('permission_names') ?? '');
            $this->permissions = Permission::retrieve(Permission::table()->select()->whereStringIn('name', $permission_unique_keys));
        } elseif (property_exists($this, 'permission_ids') && !is_null($this->get('permission_ids'))) {
            $permission_unique_keys = explode(',', $this->get('permission_ids') ?? '');
            $this->permissions = Permission::retrieve(Permission::table()->select()->whereNumericIn('id', $permission_unique_keys));
        } else {
            $this->permissions = ACL::permissions_for($this);
        }

        return $this->permissions;
    }

    public function hasPermission($p): bool
    {
      // new instances or inactive operators, none shall pass
        if ($this->isNew() === true) {
            return false;
        }
        if ($this->isActive()  === false) {
            return false;
        }
        $permission_name = null;
        $permission_id = null;
        if (is_subclass_of($p, '\HexMakina\kadro\Auth\Permission')) {
            $permission_name = $p->get('name');
            $permission_id = $p->getId();
        } elseif (preg_match('#\d+#', $p)) {
            $permission_id = $p;
        } else {
            $permission_name = $p;
        }
        if (!is_null($this->get('permission_names')) && !is_null($permission_name)) {
            return strpos($this->get('permission_names'), $permission_name) !== false;
        }

        if (!is_null($this->get('permission_ids')) && !is_null($permission_id)) {
            return strpos($this->get('permission_ids'), $permission_id) !== false;
        } elseif (!is_null($permission_name)) {
            if (method_exists($this, $permission_name) && $this->$permission_name() == true) {
                return true;
            }
            if (property_exists($this, $permission_name) && $this->get('$permission_name') == true) {
                return true;
            }
            elseif (ACL::match($this, $permission_name) === true) {
                return true;
            }
        }

        return false;
    }

    private function validate_password($string): string
    {
        if (empty($string)) {
            throw new \InvalidArgumentException('PASSWORD_CANT_BE_EMPTY');
        }

        return $string;
    }
}
