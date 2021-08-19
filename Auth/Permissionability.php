<?php

namespace HexMakina\kadro\Auth;

trait Permissionability
{


    abstract public function get($prop_name);
    abstract public function is_new(): bool;
    abstract public function is_active(): bool;


}
