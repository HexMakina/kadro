<?php 

namespace HexMakina\kadro\Models;

abstract class BaseModel
{
	public function is_new()
	{
		return true;
	}

	public function get($prop_name)
	{
		if(property_exists($this, $prop_name) === true)
			return $this->$prop_name;

		return null;
	}

	public function set($prop_name, $value)
	{
		$this->$prop_name = $value;
	}

  public function import($dat_ass)
	{
		if(!is_array($dat_ass))
			ddt($dat_ass, __FUNCTION__.'(assoc_data)');

    // shove it all up in model, god will sort them out
    foreach($dat_ass as $prop_name => $value)
      $this->set($prop_name, $value);

		return $this;
	}

	public static function class_short_name()
	{
		return (new \ReflectionClass(get_called_class()))->getShortName();
	}

	public static function model_type() : string
	{
		return strtolower(self::class_short_name());
	}	  
}
?>
