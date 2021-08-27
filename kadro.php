<?php

namespace HexMakina\kadro;

use \HexMakina\LeMarchand\LeMarchand;
use \HexMakina\Debugger\Debugger;
use \HexMakina\Lezer\Lezer;
use \HexMakina\Smith\Smith;
use \HexMakina\LogLaddy\LogLaddy;
use \HexMakina\Hopper\hopper;

class kadro
{
    private static $box; // PSR-11 Service Locator, ugly until DI is ready


    public static function init($settings)
    {
      new Debugger();

      self::$box=new LeMarchand($settings);

      //-- logger
      self::$box->register('LoggerInterface', self::reporting());

      //-- router
      self::$box->register('RouterInterface', self::routing());

      //-- sessions, ans soon cookies
      self::$box->register('StateAgent', self::state());


      self::internationalisation();

      // ----     ŝablonoj
      self::$box->register('template_engine', self::templating());

      // ----     lingva
      $locale_path = self::$box->get('settings.locale.directory_path');
      $file_name = self::$box->get('settings.locale.file_name');
      $fallback_lang = self::$box->get('settings.locale.fallback_lang');

      $lezer = new Lezer($locale_path.'/'.$file_name, $locale_path.'/cache', $fallback_lang);
      $language = $lezer->availableLanguage();
      $lezer->init();

      self::$box->get('template_engine')->assign('lezer', $lezer);
      self::$box->get('template_engine')->assign('language', $language);

      setcookie('lang', $language, time()+(365 * 24 * 60 * 60), "/", "");

      return self::$box;
    }


    private static function reporting(): \HexMakina\LogLaddy\LoggerInterface
    {
      //-- logger
      error_reporting(E_ALL);
      define('PRODUCTION', $_SERVER['HTTP_HOST'] === self::$box->get('settings.app.production_host'));
      ini_set('display_errors', PRODUCTION ? 0 : 1);

      $log_laddy = new \HexMakina\LogLaddy\LogLaddy();
      $log_laddy->set_handlers();
      return $log_laddy;
    }

    private static function routing()
    {
      $Hup = new \HexMakina\Hopper\hopper(self::$box->get('settings.RouterInterface'));

      $Hup->map( 'GET' ,  'checkin', 'ReceptionController::checkin', 'checkin');
      $Hup->map( 'GET' ,  'checkout', 'ReceptionController::checkout', 'checkout');
      $Hup->map( 'POST' , 'identify', 'ReceptionController::identify', 'identify');

      $Hup->map( 'GET' ,  'operator/[*:username]/toggle/active' , 'OperatorController::change_active', 'operator_change_active');
      $Hup->map( 'GET' ,  'operator/[*:username]/change-acl/[i:permission_id]' , 'OperatorController::change_acl', 'acl_toggle');


      // ---------------------------------------------------- SEARCH
      // $Hup->map( 'GET' , 'search', 'SearchController::search', 'search_advanced');
      $Hup->map( 'POST|GET' , 'search', 'SearchController::results', 'search');
      // $Hup->map( 'POST' , 'search', 'SearchController::results', 'search');

      // ---------------------------------------------------- TRADUKO
      $Hup->map( 'POST' , 'traduko/update_file', 'TradukoController::update_file', 'traduko_update_file');

      // ---------------------------------------------------- LOCALE JSON
      $Hup->map( 'GET' , 'locale/language_codes.[a:format]', 'ExportController::otto_languages', 'otto_languages');


      // ---------------------------------------------------- EXPORT
      $Hup->map( 'GET' , 'export', 'ExportController::dashboard', 'export'); // default ExportController is dashboard
      $Hup->map( 'GET' , 'otto/language_codes.[a:format]/term/[a:search]?', 'ExportController::otto_languages', 'otto_languages_search');
      $Hup->map( 'GET' , 'otto/[a:model]/distinct/[*:field].[a:format]', 'ExportController::otto_distinct_field', 'otto_distinct_field');
      $Hup->map( 'GET' , 'otto/[a:model]/distinct/[*:field].[a:format]/term/[*:search]?', 'ExportController::otto_distinct_field', 'otto_distinct_field_where');
      $Hup->map( 'GET' , 'export/[*:action].[a:format]', 'ExportController::dynamic_action_call', 'export_action_call');

      return $Hup;
    }

    private static function state()
    {
      //--  kuketoj
      setcookie('cookie_test', 'test_value', time()+(365 * 24 * 60 * 60), "/", "");
      $cookies_enabled=isset($_COOKIE['cookie_test']); // houston, do we have cookies ?

      if($cookies_enabled === false)
      {
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        ini_set('session.use_trans_sid', 1);
        ini_set('session.cache_limiter', 'nocache');
      }

      //--  Session Management
      $StateAgent = new \HexMakina\Smith\Smith(self::$box->get('settings.app.session_start_options') ?? []);
      $StateAgent->add_runtime_filters((array)self::$box->get('settings.filter'));
      $StateAgent->add_runtime_filters((array)($_SESSION['filter'] ?? []));
      $StateAgent->add_runtime_filters((array)($_REQUEST['filter'] ?? []));

      return $StateAgent;
    }

    private static function internationalisation()
    {

        // ----     parametroj:signo
        $setting = 'settings.default.charset';
        if(is_string(self::$box->get($setting)))
        {
          ini_set('default_charset', self::$box->get($setting));
          header('Content-type: text/html; charset='.strtolower(self::$box->get($setting)));
        }
        else
          throw new \UnexpectedValueException($setting);

        // ----     parametroj:linguo
        $setting = 'settings.default.language';
        if(is_string(self::$box->get($setting)))
        {
          putenv('LANG='.self::$box->get($setting));
          setlocale(LC_ALL, self::$box->get($setting));
        }
        else
          throw new \UnexpectedValueException($setting);

        // ----     parametroj:datoj
        $setting = 'settings.default.timezone';
        if(is_string(self::$box->get($setting)))
          date_default_timezone_set(self::$box->get($setting));
        else
          throw new \UnexpectedValueException($setting);

    }

    private static function templating()
    {
      $smarty=new \Smarty();
      self::$box->register('template_engine', $smarty);

      // Load smarty template parser
      $smarty->setTemplateDir(self::$box->get('settings.smarty.template_app_directory'));

      foreach(self::$box->get('settings.smarty.template_extra_directories') as $i => $template_dir)
      {
          $smarty->addTemplateDir($template_dir);
      }
      $smarty->addTemplateDir(__DIR__.'/Views/'); //kadro templates

      $setting = 'settings.smarty.compiled_path';
      if(is_string(self::$box->get($setting)))
        $smarty->setCompileDir(self::$box->get($setting));
      else
        throw new \UnexpectedValueException($setting);

      $setting = 'settings.smarty.debug';
      if(is_bool(self::$box->get($setting)))
        $smarty->setDebugging(self::$box->get($setting));
      else
        throw new \UnexpectedValueException($setting);

      $smarty->registerClass('Lezer','\HexMakina\Lezer\Lezer');
      $smarty->registerClass('Marker','\HexMakina\Marker\Marker');
      $smarty->registerClass('Form','\HexMakina\Marker\Form');
      $smarty->registerClass('TableToForm','\HexMakina\kadro\TableToForm');
      $smarty->registerClass('Dato','\HexMakina\Tempus\Dato');

      $smarty->assign('APP_NAME', self::$box->get('settings.app.name'));

      return $smarty;
    }

}
