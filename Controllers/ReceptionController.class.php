<?php

namespace HexMakina\kadro\Controllers;

use \Psr\Container\ContainerInterface;
use HexMakina\kadro\Auth\{Operator,Permission,ACL};
use HexMakina\kadro\Auth\{OperatorInterface,AccessRefusedException};

class ReceptionController extends KadroController
{
  private $guest = null;

  public function requires_operator()
  {
    return false;
  }

  public function welcome(OperatorInterface $operator)
  {

    if($this->router()->name() === 'identify')
      $this->identify($operator);

    $Controller = $this->router()->target_controller();
    $Controller = $this->box($Controller);


    if($Controller->requires_operator())
    {
      if(is_null($operator = get_class($operator)::exists($this->box('StateAgent')->operator_id())))
        $this->router()->hop('checkin');

      if(!$operator->is_active())
      {
        $this->checkout();
        throw new AccessRefusedException();
      }
      $this->box('OperatorInterface', $operator);
    }

    return $operator;
  }

  public function checkin()
  {
    $this->display('checkin', 'standalone');
    $this->logger()->clean_user_report();
    die;
  }

  public function checkout()
  {
    $this->box('StateAgent')->destroy();
    $this->router()->hop('checkin');
  }

  public function identify($op)
  {
    try {
      $username = $this->router()->submitted('username');
      $password = $this->router()->submitted('password');

      $operator = get_class($op)::exists(['username' => $username]);

      if(is_null($operator) || !$operator->is_active())
        throw new \Exception('ERR_DISABLED');

      if(!$operator->password_verify($password))
        throw new \Exception('ERR_WRONG_LOGIN_OR_PASSWORD');

      $this->box('StateAgent')->operator_id($operator->get_id());
      $this->logger()->nice('PAGE_CHECKIN_WELCOME', ["$operator"]);
      $this->router()->hop();

    } catch (\Exception $e) {
      $this->logger()->warning('KADRO_operator_'.$e->getMessage());
      $this->router()->hop('checkin');
    }
  }

}

?>
