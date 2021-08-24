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
  public static function init($settings)
  {
    new Debugger();

    $box=new LeMarchand($settings);

    //-- logger
    error_reporting(E_ALL);
    define('PRODUCTION', $_SERVER['HTTP_HOST'] === $box->get('settings.app.production_host'));
    ini_set('display_errors', PRODUCTION ? 0 : 1);

    $log_laddy = new \HexMakina\LogLaddy\LogLaddy();
    $log_laddy->set_handlers();

    $box->register('LoggerInterface', $log_laddy);


    //TODO has to be made instance method
    // set_error_handler('\HexMakina\LogLaddy\LogLaddy::error_handler');
    // set_exception_handler('\HexMakina\LogLaddy\LogLaddy::exception_handler');

    //-- router
    $box->register('RouterInterface', new \HexMakina\Hopper\hopper($box->get('settings.RouterInterface')));

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
    $StateAgent = new \HexMakina\Smith\Smith($box->get('settings.app.session_start_options') ?? []);
    $StateAgent->add_runtime_filters((array)$box->get('settings.filter'));
    $StateAgent->add_runtime_filters((array)($_SESSION['filter'] ?? []));
    $StateAgent->add_runtime_filters((array)($_REQUEST['filter'] ?? []));

    $box->register('StateAgent', $StateAgent);


    // ----     parametroj:signo
    $setting = 'settings.default.charset';
    if(is_string($box->get($setting)))
    {
      ini_set('default_charset', $box->get($setting));
      header('Content-type: text/html; charset='.strtolower($box->get($setting)));
    }
    else
      throw new \UnexpectedValueException($setting);

    // ----     parametroj:linguo
    $setting = 'settings.default.language';
    if(is_string($box->get($setting)))
    {
      putenv('LANG='.$box->get($setting));
      setlocale(LC_ALL, $box->get($setting));
    }
    else
      throw new \UnexpectedValueException($setting);

    // ----     parametroj:datoj
    $setting = 'settings.default.timezone';
    if(is_string($box->get($setting)))
      date_default_timezone_set($box->get($setting));
    else
      throw new \UnexpectedValueException($setting);

    // ----     ŝablonoj
    // require_once 'smarty/smarty/libs/Smarty.class.php';
    $smarty = self::smarty_configurator($box);

    // ----     lingva
    $locale_path = $box->get('settings.locale.directory_path');
    $file_name = $box->get('settings.locale.file_name');
    $fallback_lang = $box->get('settings.locale.fallback_lang');

    $lezer = new Lezer($locale_path.'/'.$file_name, $locale_path.'/cache', $fallback_lang);
    $language = $lezer->availableLanguage();

    $lezer->init();

    $smarty->assign('language', $language);
    if($cookies_enabled === true)
      setcookie('lang', $language, time()+(365 * 24 * 60 * 60), "/", "");

    return $box;
   }

    private static function smarty_configurator($box)
    {
      $smarty=new \Smarty();
      $box->register('template_engine', $smarty);

      // Load smarty template parser
      $smarty->setTemplateDir($box->get('settings.smarty.template_app_directory'));

      foreach($box->get('settings.smarty.template_extra_directories') as $i => $template_dir)
      {
          $smarty->addTemplateDir($template_dir);
      }
      $smarty->addTemplateDir(__DIR__.'/Views/'); //kadro templates

      $setting = 'settings.smarty.compiled_path';
      if(is_string($box->get($setting)))
        $smarty->setCompileDir($box->get($setting));
      else
        throw new \UnexpectedValueException($setting);

      $setting = 'settings.smarty.debug';
      if(is_bool($box->get($setting)))
        $smarty->setDebugging($box->get($setting));
      else
        throw new \UnexpectedValueException($setting);

      $smarty->registerClass('Lezer','\HexMakina\Lezer\Lezer');
      $smarty->registerClass('Marker','\HexMakina\Marker\Marker');
      $smarty->registerClass('Form','\HexMakina\Marker\Form');
      $smarty->registerClass('TableToForm','\HexMakina\kadro\TableToForm');
      $smarty->registerClass('Dato','\HexMakina\Tempus\Dato');

      $smarty->assign('APP_NAME', $box->get('settings.app.name'));

      return $smarty;
    }
}
