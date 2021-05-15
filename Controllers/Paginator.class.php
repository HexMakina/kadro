<?php

namespace HexMakina\kadro\Controllers;


trait Paginator
{
  protected static function prepare_pagination($records_total)
  {
  	global $settings;
  	global $smarty;

    $pages_max_on_each_side = 3;
    $pages_max_displayed = $pages_max_on_each_side*2+1;

    // are we paginating ?

    if(is_null($this->box('StateAgent')->filters('results_per_page')))
      return;

    $pages_range    = intval($this->box('StateAgent')->filters('results_per_page'));
    $pages_total    = $pages_range > 0 ? intval(ceil($records_total / $pages_range)) : 1;
    $pages_current  = intval($this->box('StateAgent')->filters('page'));

    if($pages_current >= $pages_total)
      $pages_current = $pages_total;

    $pages_first = ($pages_current <= $pages_max_on_each_side)? 1 : $pages_current - $pages_max_on_each_side;

    $pages_last = $pages_current + $pages_max_on_each_side;

    if($pages_last < $pages_max_displayed) // add the missing pages to fullfill $pages_max_displayed
      $pages_last += $pages_max_displayed-$pages_last;

    if($pages_last > $pages_total) // $pages_max_displayed greater than the total of pages
      $pages_last = $pages_total;

  	$this->viewport("pages_total",     $pages_total);
  	$this->viewport("pages_first",     $pages_first);
    $this->viewport("pages_previous",  $pages_current <= 1 ? $pages_total : $pages_current - 1);
  	$this->viewport("pages_current",   $pages_current);
  	$this->viewport("pages_next",      $pages_current >= $pages_total ? 1 : $pages_current + 1);
  	$this->viewport("pages_last",      $pages_last);
  }

}

// numbers as people: fucking is X => impair et pair font impair, uniquement pxi : p, pxp : p, ixi : i -> social consequences?
?>
