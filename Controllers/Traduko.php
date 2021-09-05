<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\LocalFS\Text\JSON;

class Traduko extends \HexMakina\kadro\Controllers\ORM
{

    const JSON_FILENAME = 'user_interface_{LANGUAGE}.json';

    public function authorize($permission = null): bool
    {
        return parent::authorize('group_admin');
    }

    public function route_back($route_name = null, $route_params = []): string
    {
        return $this->router()->hyp('traduko');
    }

    public function update_file($lang = 'fra')
    {
        try {
            $locale_path = $this->get('settings.locale.directory_path') . '/' . $this->get('settings.locale.file_name');
            self::create_file($locale_path, $lang);

            $this->logger()->nice($this->l('KADRO_SYSTEM_FILE_UPDATED'));
        } catch (\Exception $e) {
            $this->logger()->notice($this->l('KADRO_SYSTEM_FILE_UPDATED'));
        }
        $this->router()->hop('traduko');
    }

    public static function create_file($locale_path, $lang)
    {
        $res = Traduko::filter(['lang' => $lang]);
        $assoc = [];
        foreach ($res as $id => $trad) {
            if (!isset($assoc[$trad->kategorio])) {
                $assoc[$trad->kategorio] = [];
            }
            if (!isset($assoc[$trad->kategorio][$trad->sekcio])) {
                $assoc[$trad->kategorio][$trad->sekcio] = [];
            }

            $assoc[$trad->kategorio][$trad->sekcio][$trad->referenco] = $trad->$lang;
        }

        $file_path = str_replace('{LANGUAGE}', $lang, $locale_path);

        $file = new JSON($file_path, 'w+');
        $file->set_content(JSON::from_php($assoc));
    }

    public static function init($locale_path)
    {
        $languages = array_keys(array_slice(Traduko::inspect(Traduko::table_name())->columns(), 4));
        foreach ($languages as $l) {
            self::create_file($locale_path, $l);
        }

        return $languages;
    }
}
