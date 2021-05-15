<?php

namespace HexMakina\kadro\Controllers;

use \HexMakina\kadro\Models\Lingvo;

class LingvoController extends \HexMakina\kadro\Controllers\ORMController
{

  // http://www-01.sil.org/iso639-3/iso-639-3.tab
  // https://iso639-3.sil.org/code_tables/download_tables
  // const ISO_TAB_FILE = __DIR__.'/Lezer/iso-639-3_20180123.tab';
  // const ISO_TAB_FILE = __DIR__.'/Lezer/iso-639-3_20190304.tab';
  const GLOTTOLOG_FILE = __DIR__.'/Lezer/languoid.csv';

  
  public function dashboard()
  {
    $this->viewport('ISO_SCOPES', Lingvo::ISO_SCOPES);
    $this->viewport('ISO_TYPES', Lingvo::ISO_TYPES);
    return parent::dashboard();
  }
  public function listing()
  {
    parent::listing();
  }


  public static function load_glottolog_file()
  {
    $language_data = file_get_contents(self::GLOTTOLOG_FILE);
    $language_data = explode("\n", $language_data);
    $headers = explode(',', trim($language_data[0]));
    array_shift($language_data);

    foreach($language_data as $i => $lang)
    {
      $data = array_combine($headers, explode(',', $lang));

      // ...
    }
  }
}
