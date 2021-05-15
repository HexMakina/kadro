<?php

namespace HexMakina\kadro\Controllers;

trait Breadcrumb
{
  protected $breadcrumbs = [];

  public function breadcrumbs() : array
  {
    return $this->breadcrumbs;
  }
  
  public function breadcrumb($show, $route=null)
  {
    $this->breadcrumbs[]= ['show' => $show, 'route' => $route];
  }
  
  public function reset_breadcrumbs() : array
  {
    $ret = $this->breadcrumbs;
    $this->breadcrumbs = [];
    return $ret;
  }
}
?>
