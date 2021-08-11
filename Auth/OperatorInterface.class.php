<?php

namespace HexMakina\kadro\Auth;

interface OperatorInterface
{
  public function is_active() : bool;
  public function is_new() : bool;


  public function operator_id();

  public function username();
  public function password();

  public function password_change($string);
  public function password_verify($string) : bool;

  public function language_code();
  public function email();
  public function phone();
  public function name();

  public function has_permission($p) : bool;
}
