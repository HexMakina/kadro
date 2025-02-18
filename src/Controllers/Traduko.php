<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\LocalFS\Text\JSON;

use \HexMakina\kadro\Models\Traduko as Model;

class Traduko extends \HexMakina\kadro\Controllers\ORM
{
    /**
     * @var string
     */
    public const JSON_FILENAME = 'user_interface_{LANGUAGE}.json';

    public function authorize($permission = null): bool
    {
        return parent::authorize('group_admin');
    }

    public function routeBack($route_name = null, $route_params = []): string
    {
        return $this->router()->hyp('traduko');
    }

    public function update_file($lang = 'fra'): void
    {
        try {
            $locale_path = $this->get('settings.locale.json_path');
            self::create_file($locale_path, $lang);

            $this->logger()->notice($this->l('KADRO_SYSTEM_FILE_UPDATED'));
        } catch (\Exception $exception) {
            $this->logger()->warning($this->l('KADRO_SYSTEM_FILE_UPDATED'));
        }

        $this->router()->hop('traduko');
    }

    public static function create_file($locale_path, $lang): void
    {
        $res = \HexMakina\kadro\Models\Traduko::filter(['lang' => $lang]);
        $assoc = [];
        foreach ($res as $re) {
            if (!isset($assoc[$re->kategorio])) {
                $assoc[$re->kategorio] = [];
            }

            if (!isset($assoc[$re->kategorio][$re->sekcio])) {
                $assoc[$re->kategorio][$re->sekcio] = [];
            }

            $assoc[$re->kategorio][$re->sekcio][$re->referenco] = $re->$lang;
        }

        $file_path = str_replace('{LANGUAGE}', $lang, $locale_path);

        $json = new JSON($file_path, 'w+');
        $json->set_content(JSON::from_php($assoc));
    }

    /**
     * @return int[]|string[]
     */
    public static function init($locale_path): array
    {
        $languages = array_keys(array_slice(Model::database()->inspect(Model::table())->columns(), 4));
        foreach ($languages as $language) {
            self::create_file($locale_path, $language);
        }

        return $languages;
    }
}
