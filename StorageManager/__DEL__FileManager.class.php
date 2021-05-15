<?php

namespace HexMakina\kadro\StorageManager;

require_once 'qivive/curlyb.php';

class FileManager
{
  // protected $pmi = null; // FileManageable-Model
  protected $picture_type = null; // Purpose of the file (movie poster, album cover, poem, film)
  protected $pmi = null; // Purpose of the file (movie poster, album cover, poem, film)
  
  function __construct($file_manageable_model, $filetype)
  {
    if(!is_subclass_of($file_manageable_model, '\HexMakina\ORM\TightModel'))
      throw new \Exception("__construct($file_manageable_model) // only Model items are manageable");

    $this->pmi = $file_manageable_model;
    $this->picture_type = $filetype;
  }

  public function get_type()
  {
    return $this->picture_type;
  }
  // FileSystem::move() 
  // public function rename($move_this, $to_that)
  // {
  //   return rename($move_this, $to_that);
  // }
  
  public function download($url)
  {
    $filename = $this->build_filename();
    $filename .= '.' . pathinfo($url, PATHINFO_EXTENSION);

    $filepath = $this->build_path_to_file($filename);

    \qivive\Curlyb::fetch($url, $filepath);

    $this->make_thumbnail($filepath);
  }

  public function locate_file($filename)
  {
      $location = $this->build_path_to_directory().$filename;

      return $location;
  }

  public function build_path_to_file($filename)
  {
      return $this->build_path_to_directory().$filename;
  }

  protected function build_path_to_directory()
  {
    return $this->pmi->get_id().'/';  

    $pmi_model_type = get_class($this->pmi)::model_type();
    if(!isset($this->pmi) || is_null($this->get_type()))
      throw new \Exception(__FUNCTION__." // undefined manageable item or picture type");

    if(!isset($settings[$pmi_model_type][$this->get_type()]['import_directory']))
      throw new \Exception(__FUNCTION__." // undefined configuration for '".($pmi_model_type)."'>'".$this->get_type()."'>'import_directory'");
    
    // global $settings;
    // return $settings[$pmi_model_type][$this->get_type()]['import_directory'].$this->pmi->get_id().'/';
  }

  // DEPRECATED, SHOULD BE IMPLEMENTED IN Format/File.class as info()
  // public static function file_info($absolute_path_to_picture, $what=null)
  // {
  //   if(!file_exists($absolute_path_to_picture))
  //     return null;
  // 
  //   $ret = pathinfo($absolute_path_to_picture);
  //   unset($ret['basename']);
  //   $ret['size'] = filesize($absolute_path_to_picture);
  // 
  //   return $ret;
  // }
  // DEPRECATED, SHOULD BE IMPLEMENTED IN Format/File.class as extension()
  // protected static function file_ext($file_name)
  // {
  //   return pathinfo($file_name, PATHINFO_EXTENSION);
  // }

  // DEPRECATED, IMPLEMENTED IN Format/FileSystem.class
  // public static function preg_scandir($dir_path, $regex=null)
  // {
  //   if(!file_exists($dir_path) || !is_dir($dir_path))
  //     return null;
  // 
  //   if(($filenames = scandir($dir_path, SCANDIR_SORT_ASCENDING)) !== false)
  //     return is_null($regex)? $filenames : preg_grep($regex, $filenames);
  // 
  //   throw new \Exception("directory path '$dir_path' cannot be scanned");
  // }

}

 ?>
