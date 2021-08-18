<?php

namespace HexMakina\kadro\Controllers\Interfaces;

interface DisplayControllerInterface extends BaseControllerInterface
{

   /*
      The viewport is an associative array of values to be exported as variables in the view
         the assoc keys will be the variable names

      It follows 2 simple rules:
      Rule 1 you can only set a key, not alter it

             protects a child controller's viewport from the defaults of a parent
             when the parent is called *after* the child's execution

      Rule 2 you can't set a key to a null value

             allows the method to act as a setter *and* a getter

      Rules 1 & 2 can be broken using the coercion param set to boolean(true)


      USAGE
      ->viewport()
           returns [k=>v] of all variables

      ->viewport(string $key)
           returns mixed value at index $key or null if $key not set

      ->viewport(string $key, mixed $value)
           sets $value at index $key if index $key is not already set
           returns mixed $value at index $key

      ->viewport(string $key, mixed $value, true)
           coerces $value at index $key (value can be null)
           returns mixed $value at index $key

      ->viewport(string $key, null, true)
           coerces null at index $key
           returns null
   */

    public function viewport($key = null, $value = null, $coercion = false);

    public function display($custom_template = null, $standalone = false);
}
