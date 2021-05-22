<?php

namespace HexMakina\kadro\Auth;

// use \HexMakina\kadro\Auth\{OperatorInterface};

trait Operatorability
{
  private $operator = null;

  // auto build an operator (once) then returns it
  // throws Exception if unable to build due to missing required property
  public function set_operator(OperatorInterface $setter)
  {
    $this->operator = $setter;
  }

  public function load_operator($id = null)
  {
    if(!is_null($operator_id = $id ?? $this->get('operator_id'))) // extraction failed but we have an fk
      $this->operator = Operator::exists($operator_id);
  }

  public function operator() : ?OperatorInterface
  {
    if(is_null($this->operator))
    {
      $extract_attempt = $this->extract(new Operator(), true);
      if(!is_null($extract_attempt))
      {
        foreach(['permission_names', 'permission_ids'] as $permission_marker)
          if(property_exists($this, $permission_marker))
            $extract_attempt->set($permission_marker, $this->$permission_marker);

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
		$joined_alias = $Query->auto_join([ACL::table(),'ACL'], null, 'LEFT OUTER');
		$joined_alias = $Query->auto_join([Permission::table(), 'permission'], null, 'LEFT OUTER');

    $permission_ids_and_names = [];
    $permission_ids_and_names []= sprintf('GROUP_CONCAT(DISTINCT %s.%s) as %s', $joined_alias, 'id', $joined_alias.'_ids');
    $permission_ids_and_names []= sprintf('GROUP_CONCAT(DISTINCT %s.%s) as %s', $joined_alias, 'name', $joined_alias.'_names');
    $Query->select_also($permission_ids_and_names);

    $Query->select_also(['operator.name as operator_name', 'operator.active as operator_active']);

    if(isset($filters['username']))
      $Query->aw_eq('username', $filters['username'], 'operator');

    if(isset($filters['email']))
      $Query->aw_eq('email', $filters['email'], 'operator');

    if(isset($filters['active']))
      $Query->aw_eq('active', $filters['active'], 'operator');

    return $Query;
  }

	public function is_active() : bool
  {
    return is_null($this->operator()) ? false : $this->operator()->is_active();
  }

  public function operator_id()
  {
    return is_null($this->operator()) ? null : $this->operator()->operator_id();
  }

	public function username()
  {
    return is_null($this->operator()) ? null : $this->operator()->username();
  }

	public function password()
  {
    return is_null($this->operator()) ? null : $this->operator()->password();
  }

  public function password_change($string)
  {
    $this->operator()->password_change($string);
  }

  public function password_verify($string) : bool
  {
    return $this->operator()->password_verify($string);
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

	public function language_code()
  {
    return is_null($this->operator()) ? null : $this->operator()->language_code();
  }
}

?>
