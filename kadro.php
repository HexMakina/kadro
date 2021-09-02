<?php

namespace HexMakina\kadro;

use HexMakina\Debugger\Debugger;
use HexMakina\Hopper\hopper;
use HexMakina\LeMarchand\LeMarchand;
use HexMakina\Lezer\Lezer;
use HexMakina\LogLaddy\LogLaddy;
use HexMakina\Smith\Smith;

class kadro
{
    private static $box; // PSR-11 Service Locator, ugly until DI is ready


    public static function init($settings)
    {
        new Debugger();

        self::$box = LeMarchand::box($settings);

      //-- logger
        self::$box->put('LoggerInterface', self::reporting());

      //-- router
        self::$box->put('RouterInterface', self::routing());

      //-- sessions, ans soon cookies
        self::$box->put('StateAgent', self::state());


        self::internationalisation();

      // ----     ŝablonoj
        self::$box->put('template_engine', self::templating());

      // ----     lingva
        $json_path = self::$box->get('settings.locale.json_path');
        $cache_path = self::$box->get('settings.locale.cache_path');
        $fallback_lang = self::$box->get('settings.locale.fallback_lang');
        $lezer = new Lezer($json_path, $cache_path, $fallback_lang);
        $language = $lezer->availableLanguage();
        $lezer->init();

        self::$box->get('template_engine')->assign('lezer', $lezer);
        self::$box->get('template_engine')->assign('language', $language);

        setcookie('lang', $language, time() + (365 * 24 * 60 * 60), "/", "");

        return self::$box;
    }


    private static function reporting(): \HexMakina\LogLaddy\LoggerInterface
    {
      //-- logger
        error_reporting(E_ALL);
        define('PRODUCTION', $_SERVER['HTTP_HOST'] === self::$box->get('settings.app.production_host'));
        ini_set('display_errors', PRODUCTION ? 0 : 1);

        $log_laddy = new \HexMakina\LogLaddy\LogLaddy();
        $log_laddy->setHandlers();
        return $log_laddy;
    }

    private static function routing()
    {
        $Hup = new \HexMakina\Hopper\hopper(require(__DIR__.'/routes.php'));
        $Hup->setBasePath(self::$box->get('settings.hopper.web_base'));
        $Hup->setFilePath(self::$box->get('settings.hopper.file_root'));
        $Hup->mapHomeRoute(self::$box->get('settings.hopper.route_home'));
        return $Hup;
    }

    private static function state()
    {
      //--  kuketoj
      // setcookie('cookie_test', 'test_value', time()+(365 * 24 * 60 * 60), "/", "");
      // $cookies_enabled=isset($_COOKIE['cookie_test']); // houston, do we have cookies ?

      // if($cookies_enabled === false)
      // {
      //   ini_set('session.use_cookies', 0);
      //   ini_set('session.use_only_cookies', 0);
      //   ini_set('session.use_trans_sid', 1);
      //   ini_set('session.cache_limiter', 'nocache');
      // }

      //--  Session Management
        $StateAgent = new \HexMakina\Smith\Smith(self::$box->get('settings.app.session_start_options') ?? []);
        $StateAgent->addRuntimeFilters((array)self::$box->get('settings.filter'));
        $StateAgent->addRuntimeFilters((array)($_SESSION['filter'] ?? []));
        $StateAgent->addRuntimeFilters((array)($_REQUEST['filter'] ?? []));

        return $StateAgent;
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
        $smarty = new \Smarty();
        self::$box->put('template_engine', $smarty);

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
