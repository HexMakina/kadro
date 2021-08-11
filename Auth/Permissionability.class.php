<?php

namespace HexMakina\kadro\Auth;

trait Permissionability
{
  protected $permissions = null;

  public function permission_names()
  {
    if(property_exists($this, 'permission_names'))
    {
      return explode(',', $this->get('permission_names'));
    }
    elseif(property_exists($this, 'permission_ids'))
    {
      $ids = explode(',', $this->get('permission_ids'));
      $ret = [];
      $permissions = Permission::get_many_by_AIPK($ids);
      foreach($permissions as $id => $p)
        $ret[]="$p";
      return $ret;
    }
    else
    {
      return ACL::permissions_names_for($this);
    }
  }

  public function permissions()
  {

    if(!is_null($this->permissions))
      return $this->permissions;
    $permission_unique_keys = null;
    if(property_exists($this, 'permission_names'))
    {
      $permission_unique_keys = explode(',', $this->permission_names);
      $this->permissions = Permission::retrieve(Permission::table()->select()->aw_string_in('name', $permission_unique_keys));
    }
    elseif(property_exists($this, 'permission_ids'))
    {
      $permission_unique_keys = explode(',', $this->permission_ids);
      $this->permissions = Permission::retrieve(Permission::table()->select()->aw_numeric_in('id', $permission_unique_keys));
    }
    else
    {
      $this->permissions = ACL::permissions_for($this);
    }

    return $this->permissions;
  }

  public function has_permission($p) : bool
  {
    // new instances or inactive operators, none shall pass
    if($this->is_new() === true || $this->is_active()  === false)
      return false;

    $permission_name = $permission_id = null;
    if(is_subclass_of($p, '\HexMakina\kadro\Auth\Permission'))
    {
      $permission_name = $p->get('name');
      $permission_id = $p->get_id();
    }
    elseif(preg_match('/[0-9]+/', $p))
      $permission_id = $p;
    else
      $permission_name = $p;

    if(!is_null($this->get('permission_names')) && !is_null($permission_name))
    {
      return strpos($this->get('permission_names'), $permission_name) !== false;
    }
    elseif(!is_null($this->get('permission_ids')) && !is_null($permission_id))
    {
      return strpos($this->get('permission_ids'), $permission_id) !== false;
    }
    elseif(!is_null($permission_name))
    {
      if(method_exists($this, $permission_name) && $this->$permission_name() == true)
      {
        return true;
      }
      elseif(property_exists($this, $permission_name) && $this->$permission_name == true)
      {
        return true;
      }
      elseif(ACL::match($this, $permission_name) === true)
      {
        return true;
      }
    }

    return false;
  }
}
