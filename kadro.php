<?php

namespace HexMakina\kadro;

use HexMakina\Debugger\Debugger;
use HexMakina\LeMarchand\LeMarchand;
use HexMakina\Lezer\Lezer;

class kadro
{
    private static $box; // PSR-11 Service Locator, ugly until DI is ready


    public static function init($settings)
    {
        new Debugger();

        self::$box = LeMarchand::box($settings);

        //-- error & logs & messages
        error_reporting(E_ALL);
        ini_set('display_errors', PRODUCTION ? 0 : 1);

        $log_laddy = self::$box->get('Psr\Log\LoggerInterface');


        //-- router
        $router = self::$box->get('HexMakina\BlackBox\RouterInterface');
        $router->addRoutes(require(__DIR__ . '/routes.php'));

        //-- session
        $StateAgent = self::$box->get('HexMakina\BlackBox\StateAgentInterface');
        $StateAgent->addRuntimeFilters((array)self::$box->get('settings.filter'));
        $StateAgent->addRuntimeFilters((array)($_SESSION['filter'] ?? []));
        $StateAgent->addRuntimeFilters((array)($_REQUEST['filter'] ?? []));


        self::internationalisation();

      // ----     ŝablonoj
        self::templating();

      // ----     lingva
        $json_path = self::$box->get('settings.locale.json_path');
        $cache_path = self::$box->get('settings.locale.cache_path');
        $fallback_lang = self::$box->get('settings.locale.fallback_lang');
        $lezer = new Lezer($json_path, $cache_path, $fallback_lang);
        $language = $lezer->availableLanguage();
        $lezer->init();

        self::$box->get('\Smarty')->assign('lezer', $lezer);
        self::$box->get('\Smarty')->assign('language', $language);

        setcookie('lang', $language, time() + (365 * 24 * 60 * 60), "/", "");

        return self::$box;
    }

    private static function internationalisation()
    {
        // ----     parametroj:signo
        $setting = 'settings.default.charset';
        if (is_string(self::$box->get($setting))) {
            ini_set('default_charset', self::$box->get($setting));
            header('Content-type: text/html; charset=' . strtolower(self::$box->get($setting)));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        // ----     parametroj:linguo
        $setting = 'settings.default.language';
        if (is_string(self::$box->get($setting))) {
            putenv('LANG=' . self::$box->get($setting));
            setlocale(LC_ALL, self::$box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        // ----     parametroj:datoj
        $setting = 'settings.default.timezone';
        if (is_string(self::$box->get($setting))) {
            date_default_timezone_set(self::$box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }
    }

    private static function templating()
    {
        $smarty = self::$box->get('\Smarty');
      // Load smarty template parser
        $smarty->setTemplateDir(self::$box->get('settings.smarty.template_app_directory'));

        foreach (self::$box->get('settings.smarty.template_extra_directories') as $i => $template_dir) {
            $smarty->addTemplateDir($template_dir);
        }
        $smarty->addTemplateDir(__DIR__ . '/Views/'); //kadro templates

        $setting = 'settings.smarty.compiled_path';
        if (is_string(self::$box->get($setting))) {
            $smarty->setCompileDir(self::$box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        $setting = 'settings.smarty.debug';
        if (is_bool(self::$box->get($setting))) {
            $smarty->setDebugging(self::$box->get($setting));
        } else {
            throw new \UnexpectedValueException($setting);
        }

        $smarty->registerClass('Lezer', '\HexMakina\Lezer\Lezer');
        $smarty->registerClass('Marker', '\HexMakina\Marker\Marker');
        $smarty->registerClass('Form', '\HexMakina\Marker\Form');
        $smarty->registerClass('TableToForm', '\HexMakina\kadro\TableToForm');
        $smarty->registerClass('Dato', '\HexMakina\Tempus\Dato');

        $smarty->assign('APP_NAME', self::$box->get('settings.app.name'));

        return $smarty;
    }
}
