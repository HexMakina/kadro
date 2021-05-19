<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Models\Traduko;
use \HexMakina\Format\File\Text\JSON;

class TradukoController extends \HexMakina\kadro\Controllers\ORMController
{
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
    $res = Traduko::filter(['lang' => $lang]);
    $assoc = [];
    foreach($res as $id => $trad)
    {
      $assoc[$trad->kategorio] = $assoc[$trad->kategorio] ?? [];
      $assoc[$trad->kategorio][$trad->sekcio] = $assoc[$trad->kategorio][$trad->sekcio] ?? [];
      $assoc[$trad->kategorio][$trad->sekcio][$trad->referenco] = $trad->$lang;
    }

    $report_filename = $lang.'/user_interface.json';
    $file = new JSON(APP_BASE.'locale/'.$report_filename, 'w+');
    $file->set_content(JSON::from_php($assoc));

    $this->logger()->nice('KADRO_SYSTEM_FILE_UPDATED', [$report_filename]);
    $this->router()->hop('traduko');
  }
}
