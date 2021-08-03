<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Models\Traduko;
use \HexMakina\LocalFS\Text\JSON;

class TradukoController extends \HexMakina\kadro\Controllers\ORMController
{
  const JSON_FILENAME = 'user_interface.json';

  public function authorize($permission = null)
  {
    return parent::authorize('group_admin');
  }

  public function route_back($route_name = NULL, $route_params = []) : string
	{
		return $this->router()->prehop('traduko');
	}

  public function update_file($lang='fra')
  {
    $locale_path = $this->box('settings.locale_data_path');
    self::create_file($locale_path, $lang);

    $this->logger()->nice(L('KADRO_SYSTEM_FILE_UPDATED', [$report_filename]));
    $this->router()->hop('traduko');
  }

  public static function create_file($locale_path, $lang)
  {
    $res = Traduko::filter(['lang' => $lang]);
    $assoc = [];
    foreach($res as $id => $trad)
    {
      if(!isset($assoc[$trad->kategorio]))
        $assoc[$trad->kategorio] = [];
      if(!isset($assoc[$trad->kategorio][$trad->sekcio]))
        $assoc[$trad->kategorio][$trad->sekcio] = [];

      $assoc[$trad->kategorio][$trad->sekcio][$trad->referenco] = $trad->$lang;
    }

    // try
    // {
    $path_to_file = $locale_path.'/'.$lang;
    // test directory access & creation
    if(!JSON::exists($path_to_file))
      JSON::make_dir($path_to_file);

    $file = new JSON($path_to_file.'/'.self::JSON_FILENAME, 'w+');
    $file->set_content(JSON::from_php($assoc));
    // }
    // catch(\Exception $e)
    // {
    //   ddt($e);
    // }
  }

  public static function init($locale_path)
  {
    $languages = array_keys(array_slice(Traduko::inspect(Traduko::table_name())->columns(), 4));
    foreach($languages as $l)
      self::create_file($locale_path, $l);

    return $languages;
  }
}
